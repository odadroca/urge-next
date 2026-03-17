# CLAUDE.md

## Project Overview

URGE v2 is a self-hosted **prompt registry and version control system** that serves two audiences:
1. **Humans** via a Livewire 3 web UI (single-screen workspace)
2. **LLMs** via a REST API, MCP server, CustomGPT actions, and Claude Skills

URGE is the prompt memory layer that sits behind any LLM. LLMs pull prompts, fill variables, resolve includes, and store results back — all via API. The UI is for curation and management.

**Stack:** Laravel 12 / PHP 8.3+, Livewire 3, Alpine.js, Tailwind CSS, SQLite, Vite

## Build & Dev Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan test         # 175 tests
php artisan serve        # http://127.0.0.1:8000
npm run dev              # Vite HMR
npm run build            # Production
```

## Architecture

### Core Concept

URGE is a **prompt registry with version control and result archiving**, accessible by both humans (UI) and machines (API/MCP).

```
                    ┌─────────────┐
  Claude (MCP) ────>│             │<──── Human (Browser)
  GPT (Actions) ──>│  URGE API   │<──── Claude Skill
  Any LLM ────────>│  + MCP      │
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              v            v            v
          Prompts    PromptVersions   Results
          (registry)  (immutable)    (archive)
```

### Data Flow

```
Prompt (type: prompt|fragment) → PromptVersion[] (immutable) → Result[] (source: api|manual|import|mcp)
Collection → CollectionItem[] (polymorphic: prompt_version|result)
```

### Core Models (9 domain tables)

- **Prompt** — name, slug (auto-generated, unique), type (prompt|fragment), category_id, tags (JSON), pinned_version_id (nullable; NULL = latest is active). Soft deletes.
- **PromptVersion** — immutable (LogicException on update). Auto-numbered per prompt. Extracts variables/includes on create. Has commit_message, variable_metadata (JSON).
- **Result** — unified response archive. source (api|manual|import|mcp), provider_name (free text), model_name (free text), llm_provider_id (FK, nullable), response_text, rating (1-5), starred (boolean), notes, token counts, duration_ms, import_filename.
- **Category** — name, slug (auto-generated), color
- **LlmProvider** — name, driver, api_key (encrypted), model, endpoint, settings (JSON)
- **Collection** — title, slug (auto-generated, unique), description, created_by. Soft deletes.
- **CollectionItem** — collection_id, item_type+item_id (polymorphic via enforceMorphMap: prompt_version|result), sort_order, notes. Unique constraint on [collection_id, item_type, item_id].

### Integration Surfaces

| Surface | Protocol | Consumer |
|---|---|---|
| REST API | JSON over HTTP, Bearer token auth | Any HTTP client, CustomGPT Actions |
| MCP Server (SSE) | HTTP SSE transport, Bearer auth, Model Context Protocol | Remote MCP clients (Claude Desktop pointing at hosted URGE) |
| MCP Server (stdio) | stdio transport, Model Context Protocol | Local MCP clients (Claude Code, Claude Desktop on same machine) |
| Claude Skill | Markdown instructions + API calls | Claude Projects |
| Web UI | Livewire 3 (HTML over AJAX) | Humans in browsers |

### MCP Server (dual transport)

**SSE (primary, for remote/hosted URGE):** HTTP endpoint at `/mcp`, authenticated via Bearer token (same API keys). Claude Desktop on your local machine connects to your hosted URGE instance over the network.

**stdio (secondary, for local dev):** Artisan command `php artisan urge:mcp-server`. Same handler logic, different transport wrapper.

Both transports share the same tool dispatch layer — the handler resolves tool calls to service layer methods identically.

**Tools:**
- `get_prompt(slug, version?, variables?)` — fetch, optionally render with variables
- `list_prompts(type?, category?, tag?, search?)` — browse registry
- `save_version(slug, content, commit_message?)` — create new version
- `store_result(slug, version, response_text, provider?, model?)` — archive a result
- `get_results(slug, version?, starred?)` — retrieve past results
- `render_prompt(slug, version?, variables{})` — resolve includes + fill variables, return rendered text

**Resources:**
- `urge://prompts` — list of all prompts
- `urge://prompts/{slug}` — prompt with active version content
- `urge://prompts/{slug}/v/{n}` — specific version content

### API Endpoints (prefix `/api/v1/`)

```
GET    /prompts                    — list prompts (filter: type, category, tag, search)
POST   /prompts                    — create prompt
GET    /prompts/{slug}             — get prompt with active version
GET    /prompts/{slug}/versions    — list versions
POST   /prompts/{slug}/versions    — create version
GET    /prompts/{slug}/versions/{n} — get specific version
POST   /prompts/{slug}/render      — render with variables, return text
GET    /prompts/{slug}/results     — list results
POST   /prompts/{slug}/results     — store result
GET    /results/{id}               — get single result
PATCH  /results/{id}               — update rating/starred/notes
GET    /health                     — health check
```

Auth: Bearer token → SHA-256 hash lookup. Keys scoped to specific prompts via pivot table.

### Services

- **TemplateEngine** — `{{variable}}` substitution, `{{>slug}}` recursive include resolution, circular reference detection, max depth config
- **VersioningService** — transactional version creation, auto-numbering, variable/include extraction, metadata filtering
- **ApiKeyService** — key generation (prefix + random bytes), SHA-256 hash storage, preview
- **ImportExportService** — .md with YAML frontmatter import/export (regex-based parsing, no Symfony YAML dependency)
- **LlmDispatchService** — resolves LlmProvider driver to concrete driver class, dispatches prompt. Supports `dispatch()` and `dispatchWithSystem()`.
- **AiAssistantService** — meta-prompts via `dispatchWithSystem()` for diff summarization (`summarizeDifferences`) and prompt improvement suggestions (`suggestImprovements`)

### Livewire Components

```
app/Livewire/
├── Dashboard.php              # Recent prompts, starred results, inline create
├── Browse.php                 # Tabbed (prompts/fragments/collections/starred), category+tag filters
├── Settings.php               # Tabbed settings container (API Keys, LLM Providers, Categories, Users)
├── Browse/
│   └── CollectionList.php     # Collection CRUD, expand/collapse, reorder items
├── Settings/
│   ├── ApiKeys.php            # API key CRUD, reveal, scope to prompts
│   ├── LlmProviders.php       # LLM provider CRUD, test connection, toggle active
│   ├── Categories.php         # Category CRUD with color picker
│   └── UserManagement.php     # Admin-only user role management
└── Workspace/
    ├── WorkspacePage.php      # 3-panel orchestrator
    ├── Editor.php             # Textarea, variable detection, save, export, AI suggest, Ctrl+S
    ├── VersionSidebar.php     # Version list, select, pin indicator, add-to-collection
    ├── ResultsPanel.php       # Results list, star, rate, compare, AI summarize, export
    ├── ManualResultForm.php   # Paste result with provider/model/notes/rating
    ├── ImportResults.php      # Upload .md files, preview frontmatter, import as results
    ├── RunWithLlm.php         # LLM execution: provider selection, variable fill, run, Ctrl+Enter
    └── PromptMetadata.php     # Name, type, category, tags, description
```

### Livewire Event Flow

```
VersionSidebar --[version-selected]--> WorkspacePage --> Editor, ResultsPanel, ManualResultForm, ImportResults, RunWithLlm
Editor --[version-created]--> WorkspacePage --> VersionSidebar, ResultsPanel, ImportResults, RunWithLlm
ManualResultForm --[result-saved]--> ResultsPanel
ImportResults --[result-saved]--> ResultsPanel
RunWithLlm --[result-saved]--> ResultsPanel
Editor toolbar --[toggle-run-panel]--> RunWithLlm
notify (any component) --[notify]--> app.blade.php toast system
```

### Routes

```
Web (4 screens, wire:navigate):
/dashboard, /browse, /prompts/{slug}, /settings

Internal API (no auth, same-origin only):
POST /internal/variables    — extract variables from content
GET  /internal/fragments    — list fragment slugs for autocomplete

API (prefix /api/v1/, Bearer auth):
See API Endpoints above
```

### Auth & Roles

Web: Breeze (Blade stack). Roles: admin, editor, viewer. First user auto-admin. `RequireRole` middleware as `role`.
API: `ApiKeyAuthentication` middleware, Bearer token, rate limited per key.

### Template Syntax

- `{{variable_name}}` — variable placeholder
- `{{>slug}}` — include another prompt's active version
- Max depth: `URGE_MAX_INCLUDE_DEPTH` env (default 10)

### Key Patterns

- **Blade/Alpine `{{` conflict:** Use `'{' + '{'` string splitting in JS contexts
- **Auto-slug:** Prompt, Category, and Collection generate from name/title with collision counter
- **Immutable versions:** `PromptVersion::booted()` throws LogicException on update
- **Active version:** Prompt accessor returns pinned version if set, otherwise latest

### Config

`config/urge.php` — `max_include_depth`, `curl_ssl_verify`, `api_rate_limit`, `api_rate_window`, `key_prefix`, `key_bytes`

### LLM Driver Architecture

```
app/Services/LlmProviders/
├── Contracts/LlmDriverInterface.php   # complete(prompt), completeWithSystem(system, user)
├── LlmResult.php                      # Readonly value object: success/failure factories
├── OpenAiDriver.php                   # Http::withToken(), optional baseUrl override
├── AnthropicDriver.php                # x-api-key header, top-level system param
├── MistralDriver.php                  # OpenAI-compatible endpoint
├── GeminiDriver.php                   # ?key= in URL, systemInstruction key
├── OllamaDriver.php                   # No API key, 300s timeout, configurable baseUrl
└── OpenRouterDriver.php               # Raw curl, HTTP-Referer + X-Title headers
```

Drivers: openai, anthropic, mistral, gemini, ollama, openrouter. `LlmDispatchService::resolveDriver()` maps `LlmProvider.driver` to concrete class.

### Keyboard Shortcuts (workspace)

- **Ctrl+S** / **Cmd+S** — Save version
- **Ctrl+Enter** / **Cmd+Enter** — Toggle Run LLM panel

### Toast Notifications

Global Alpine `toasts()` component in `app.blade.php`. Any Livewire component dispatches:
```php
$this->dispatch('notify', message: 'Done', type: 'success'); // or 'error'
```

### Artisan Commands

- `php artisan urge:mcp-server` — stdio MCP server
- `php artisan urge:import-v1 {path}` — import v1 SQLite database (idempotent, transaction-wrapped)

### Live Preview (Phase 6)

The Editor has a **Preview** toggle button in the toolbar. When active, the editor area splits vertically:
- **Top:** textarea/visual editor
- **Bottom:** live rendered preview with variable fill form

Preview features:
- Resolves `{{>slug}}` includes recursively (shows green badges for resolved includes)
- Fills `{{variable}}` from metadata defaults or user-overridden values in the preview form
- Shows amber badges for missing/unfilled variables
- Handles circular includes and max depth errors gracefully (red error display)
- Updates automatically as content, variables, or metadata defaults change

State: `showPreview`, `previewVariables`, `previewResult`, `previewError` on Editor component.

## Current Status

**Phases 1-6 complete.** 175+ tests passing.

### Phase Roadmap (reordered for API-first)

| Phase | Focus |
|---|---|
| 1 (done) | Core workspace UI |
| 2 (done) | API layer + MCP server + OpenAPI spec |
| 3 (done) | Rich editing (autocomplete, visual composer, diff, compare) |
| 4 (done) | Import/export + collections |
| 5 (done) | LLM drivers + AI features + v1 migration + polish |
| 6 (done) | Prompt preview (live rendered preview with includes resolved + variables filled from defaults) |
