# URGE v2 Architecture

## Vision

URGE is a **prompt registry, version control, and result archive** with two access patterns:
- **Human access** via a Livewire 3 web UI (workspace-centric, minimal navigation)
- **Machine access** via REST API + MCP server (LLMs consume and contribute to the registry)

The key insight: instead of URGE calling LLMs, **LLMs call URGE**. URGE is the memory and management layer; any LLM can pull prompts, render templates, and store results back.

## Data Model

### Entity Relationships

```
User ──< Prompt ──< PromptVersion ──< Result
              │                          │
              └── Category               └── LlmProvider (nullable)

Collection ──< CollectionItem ──> (PromptVersion | Result)

ApiKey ──<> Prompt (pivot: api_key_prompt)
```

### Tables

| Table | Key Fields | Notes |
|---|---|---|
| users | role (admin/editor/viewer) | First user auto-admin |
| prompts | slug (unique), type (prompt/fragment), pinned_version_id, tags (JSON) | Soft deletes, auto-slug |
| prompt_versions | version_number, content, variables (JSON), includes (JSON), variable_metadata (JSON) | Immutable |
| results | source (api/manual/import/mcp), provider_name, model_name, response_text, starred, rating | Unified response archive |
| categories | name, slug, color | Auto-slug |
| llm_providers | driver, api_key (encrypted), model, endpoint, settings (JSON) | 6 drivers |
| collections | title, description | Ordered groups |
| collection_items | item_type, item_id, sort_order, notes | Polymorphic |
| api_keys | key_hash (SHA-256), key_preview, is_active, expires_at | Bearer auth |
| api_key_prompt | api_key_id, prompt_id | Scope keys to prompts |

### Design Decisions

1. **Unified Result** — one table replaces v1's prompt_runs + llm_responses + library_entries. `source` column distinguishes origin. `starred` boolean replaces Library.
2. **Free-text provider/model** — manual pastes and MCP-sourced results don't need a configured provider. `llm_provider_id` only set for API-driven results.
3. **Pinned version** — NULL = latest is active. Explicit pin overrides. Cleaner than v1's active_version_id.
4. **Prompt type** — `prompt` vs `fragment`. Same model, same versioning, type flag controls include behavior.

## Integration Architecture

### Four Surfaces, One Backend

```
┌─────────────────────────────────────────────────┐
│                 Laravel App                      │
│                                                  │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ Livewire │  │ REST API │  │  MCP Server   │  │
│  │ Web UI   │  │ /api/v1/ │  │  (stdio)      │  │
│  └────┬─────┘  └────┬─────┘  └───────┬───────┘  │
│       │              │                │          │
│       └──────────────┼────────────────┘          │
│                      v                           │
│            ┌─────────────────┐                   │
│            │  Service Layer  │                   │
│            │  TemplateEngine │                   │
│            │  VersioningSvc  │                   │
│            │  ApiKeySvc      │                   │
│            │  LlmDispatchSvc │                   │
│            └────────┬────────┘                   │
│                     v                            │
│            ┌─────────────────┐                   │
│            │  Eloquent/SQLite│                   │
│            └─────────────────┘                   │
└─────────────────────────────────────────────────┘
```

### REST API (`/api/v1/`)

Bearer token auth via `ApiKeyAuthentication` middleware. Rate limited per key.

```
Prompts:
  GET    /prompts                     — list (filter: type, category, tag, search)
  POST   /prompts                     — create
  GET    /prompts/{slug}              — get with active version
  PATCH  /prompts/{slug}              — update metadata

Versions:
  GET    /prompts/{slug}/versions     — list all
  POST   /prompts/{slug}/versions     — create new
  GET    /prompts/{slug}/versions/{n} — get specific

Rendering:
  POST   /prompts/{slug}/render       — resolve includes + fill variables → text

Results:
  GET    /prompts/{slug}/results      — list (filter: version, starred)
  POST   /prompts/{slug}/results      — store
  GET    /results/{id}                — get single
  PATCH  /results/{id}                — update rating/starred/notes

System:
  GET    /health                      — health check
```

### MCP Server (dual transport)

Two transports, one shared handler layer:

**SSE transport (primary, for hosted/remote URGE):**
- HTTP endpoint at `/mcp`, authenticated via Bearer token (same API keys)
- Use case: Claude Desktop on your laptop connects to URGE on Hostinger
- Runs within the Laravel HTTP server — no extra process needed
- SSE (Server-Sent Events) for server→client streaming, POST for client→server

**stdio transport (secondary, for local dev):**
- Artisan command: `php artisan urge:mcp-server`
- Use case: Claude Code / Claude Desktop on the same machine as URGE
- Reads JSON-RPC from stdin, writes to stdout

Both transports dispatch to the same `McpToolHandler` service, which maps tool calls to TemplateEngine, VersioningService, and Eloquent queries.

**Tools:**
| Tool | Purpose |
|---|---|
| `get_prompt` | Fetch prompt by slug, optionally specific version |
| `list_prompts` | Browse/search the registry |
| `render_prompt` | Resolve includes + fill variables → rendered text |
| `save_version` | Create new version of a prompt |
| `store_result` | Archive a result (response from any LLM) |
| `get_results` | Retrieve past results for a prompt |

**Resources:**
| URI | Purpose |
|---|---|
| `urge://prompts` | List of all prompts |
| `urge://prompts/{slug}` | Prompt with active version content |
| `urge://prompts/{slug}/v/{n}` | Specific version content |

### CustomGPT Actions

OpenAPI 3.0 spec generated from the REST API. Hosted at `/api/openapi.json`. GPT custom actions import this spec directly.

### Claude Skill

Markdown file with instructions + API examples. Tells Claude how to call the URGE API with curl/fetch for prompt retrieval and result storage.

## Component Architecture

### Web UI (Livewire)

```
app/Livewire/
├── Dashboard.php              # Recent prompts, starred results, inline create
├── Browse.php                 # Tabbed: prompts, fragments, starred, collections
├── Settings.php               # Tabbed: providers, API keys, users, categories
└── Workspace/
    ├── WorkspacePage.php      # 3-panel orchestrator
    ├── Editor.php             # Content editing, variable detection
    ├── VersionSidebar.php     # Version list and selection
    ├── ResultsPanel.php       # Results display, star, rate
    ├── ManualResultForm.php   # Paste results
    └── PromptMetadata.php     # Name, type, category, tags
```

### Service Layer

```
app/Services/
├── TemplateEngine.php         # {{var}} + {{>slug}} rendering
├── VersioningService.php      # Transactional version creation
├── ApiKeyService.php          # Key generation + hashing
├── ImportExportService.php    # .md with YAML frontmatter
├── LlmDispatchService.php    # Driver dispatch (Phase 5)
├── AiAssistantService.php    # Meta-prompts for analysis (Phase 5)
└── LlmProviders/             # 6 drivers (Phase 5)
```

## Phase Roadmap

| Phase | Scope | Deliverables |
|---|---|---|
| 1 (done) | Core workspace | Models, services, Livewire workspace, 48 tests |
| 2 | API + MCP | REST API, MCP server, OpenAPI spec, Claude Skill, API key management |
| 3 | Rich editing | Autocomplete, visual composer, version diff, result comparison |
| 4 | Import/export + collections | .md import/export, collections CRUD, enhanced browse |
| 5 | LLM drivers + polish | 6 LLM drivers, AI features, v1 migration, production readiness |
