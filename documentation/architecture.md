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
| results | source, provider_name, model_name, response_text, starred, rating, rendered_content, variables_used (JSON), input_tokens, output_tokens, duration_ms, status, error_message, import_filename, created_by | Unified response archive |
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

### Five Surfaces, One Backend

```
┌──────────────────────────────────────────────────────────┐
│                      Laravel App                          │
│                                                           │
│  ┌──────────┐  ┌──────────┐  ┌─────────┐  ┌──────────┐  │
│  │ Livewire │  │ REST API │  │ MCP SSE │  │MCP stdio │  │
│  │ Web UI   │  │ /api/v1/ │  │ /mcp    │  │ artisan  │  │
│  └────┬─────┘  └────┬─────┘  └────┬────┘  └────┬─────┘  │
│       │              │             │             │        │
│       └──────────────┼─────────────┴─────────────┘        │
│                      v                                    │
│            ┌──────────────────┐                           │
│            │  Service Layer   │                           │
│            │  TemplateEngine  │                           │
│            │  VersioningSvc   │                           │
│            │  McpToolHandler  │                           │
│            │  ApiKeySvc       │                           │
│            │  LlmDispatchSvc  │                           │
│            │  AiAssistantSvc  │                           │
│            │  ImportExportSvc │                           │
│            └────────┬─────────┘                           │
│                     v                                     │
│            ┌──────────────────┐                           │
│            │  Eloquent/SQLite │                           │
│            └──────────────────┘                           │
└──────────────────────────────────────────────────────────┘

External consumers:
  Browser (human)  ──> Livewire Web UI
  Any HTTP client  ──> REST API
  Claude Desktop   ──> MCP SSE (remote) or MCP stdio (local)
  CustomGPT        ──> REST API (via OpenAPI spec)
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

### Internal Endpoints (no auth, same-origin only)

```
POST /internal/variables     — extract variables from content
GET  /internal/fragments     — list fragment slugs for autocomplete
```

Used by the Editor's inline autocomplete (Alpine.js) to suggest variable names and fragment includes.

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
├── Settings.php               # Tabbed settings container
├── Browse/
│   └── CollectionList.php     # Collection CRUD, expand/collapse, reorder items
├── Settings/
│   ├── ApiKeys.php            # API key CRUD, reveal once, scope to prompts
│   ├── LlmProviders.php      # LLM provider CRUD, test connection, toggle active
│   ├── Categories.php         # Category CRUD with color picker
│   └── UserManagement.php     # Admin-only user role management
└── Workspace/
    ├── WorkspacePage.php      # 3-panel orchestrator
    ├── Editor.php             # Content editing, live preview, visual composer, Ctrl+S
    ├── VersionSidebar.php     # Version list, select, pin, add-to-collection, diff
    ├── ResultsPanel.php       # Results list, star, rate, compare, AI summarize
    ├── ManualResultForm.php   # Paste result with provider/model/notes/rating
    ├── ImportResults.php      # Upload .md files, preview frontmatter, import
    ├── RunWithLlm.php         # LLM execution: provider selection, variable fill, run
    └── PromptMetadata.php     # Name, type, category, tags, description
```

### Service Layer

```
app/Services/
├── TemplateEngine.php         # {{var}} + {{>slug}} rendering, circular detection
├── VersioningService.php      # Transactional version creation, auto-numbering
├── ApiKeyService.php          # Key generation (prefix + random bytes), SHA-256 hashing
├── ImportExportService.php    # .md with YAML frontmatter import/export
├── McpToolHandler.php         # MCP tool dispatch (shared by SSE + stdio transports)
├── LlmDispatchService.php    # Resolve driver, dispatch prompt
├── AiAssistantService.php    # Meta-prompts: diff summarization, improvement suggestions
└── LlmProviders/
    ├── Contracts/LlmDriverInterface.php   # complete(), completeWithSystem()
    ├── LlmResult.php                      # Readonly value object
    ├── OpenAiDriver.php
    ├── AnthropicDriver.php
    ├── MistralDriver.php
    ├── GeminiDriver.php
    ├── OllamaDriver.php
    └── OpenRouterDriver.php
```

### Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan urge:mcp-server` | Start stdio MCP server for local clients |
| `php artisan urge:import-v1 {path}` | Migrate data from URGE v1 SQLite database (idempotent, transaction-wrapped) |

## Phase Roadmap

| Phase | Scope | Deliverables |
|---|---|---|
| 1 (done) | Core workspace | Models, services, Livewire workspace |
| 2 (done) | API + MCP | REST API, MCP server (SSE + stdio), OpenAPI spec, API key management |
| 3 (done) | Rich editing | Inline autocomplete, visual composer, version diff, result comparison |
| 4 (done) | Import/export + collections | .md import/export, collections CRUD, enhanced browse |
| 5 (done) | LLM drivers + AI + polish | 6 LLM drivers, AI assistant, v1 migration, settings UI, roles |
| 6 (done) | Live preview | Rendered preview with include resolution + variable fill from defaults |
