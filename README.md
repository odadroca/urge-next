# URGE v2

Self-hosted prompt registry and version control system.

## What is URGE?

URGE is a **prompt memory layer** that sits behind any LLM. Instead of URGE calling LLMs, **LLMs call URGE** — pulling prompts, filling variables, resolving includes, and storing results back via API or MCP.

Two access patterns, one backend:
- **Humans** manage and curate prompts through a Livewire 3 web UI
- **Machines** (any LLM) consume and contribute to the registry via REST API or MCP server

## Features

- **Prompt versioning** — immutable versions with auto-numbering, pin a specific version or default to latest
- **Template engine** — `{{variables}}` for substitution, `{{>slug}}` for recursive includes, circular reference detection
- **3-panel workspace** — editor, version sidebar, and results panel in a single screen
- **Live preview** — rendered preview with include resolution and variable fill from defaults
- **Visual composer** — drag-and-drop blocks (text, variable chips, include chips) via SortableJS
- **REST API** — full CRUD with Bearer token auth, rate limiting, OpenAPI 3.0 spec
- **MCP server** — dual transport (SSE for remote, stdio for local) with 6 tools and 3 resources
- **6 LLM drivers** — OpenAI, Anthropic, Mistral, Gemini, Ollama, OpenRouter
- **Import/export** — Markdown with YAML frontmatter for prompts and results
- **Collections** — curated groupings of prompt versions and results with ordering
- **Categories and tags** — organize prompts with color-coded categories and freeform tags
- **Version diff** — side-by-side comparison of any two versions
- **Result comparison** — compare 2-4 LLM responses side by side
- **Role-based access** — admin, editor, viewer roles (first user auto-admin)

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12 / PHP 8.2+ |
| Frontend | Livewire 3, Alpine.js |
| Styling | Tailwind CSS 3.1 |
| Database | SQLite (default, configurable) |
| Build | Vite 7 |
| Testing | PHPUnit 11 (185 tests) |

## Quick Start

```bash
# Install dependencies
composer install && npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate

# (Optional) Load demo data
php artisan db:seed --class=DemoSeeder

# Build frontend
npm run build

# Start server
php artisan serve
# Visit http://127.0.0.1:8000
```

For development with HMR, queue worker, and log tailing:

```bash
composer dev
```

Register at `/register` — the first user automatically becomes admin.

## API Overview

All API endpoints are under `/api/v1/` and require Bearer token authentication (except health check).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/prompts` | List prompts (filter: type, category, tag, search) |
| POST | `/prompts` | Create prompt |
| GET | `/prompts/{slug}` | Get prompt with active version |
| PATCH | `/prompts/{slug}` | Update prompt metadata |
| GET | `/prompts/{slug}/versions` | List versions |
| POST | `/prompts/{slug}/versions` | Create version |
| GET | `/prompts/{slug}/versions/{n}` | Get specific version |
| POST | `/prompts/{slug}/render` | Render template with variables |
| GET | `/prompts/{slug}/results` | List results |
| POST | `/prompts/{slug}/results` | Store result |
| GET | `/results/{id}` | Get single result |
| PATCH | `/results/{id}` | Update rating/starred/notes |

Full spec available at [`public/openapi.json`](public/openapi.json), importable as a CustomGPT Action.

## MCP Integration

URGE exposes an MCP server with two transports. Both share the same tool dispatch layer.

**SSE transport** (for hosted/remote URGE — Claude Desktop connecting over the network):

```json
{
  "mcpServers": {
    "urge": {
      "url": "https://your-urge-instance.com/api/v1/mcp",
      "headers": {
        "Authorization": "Bearer urge_YOUR_API_KEY"
      }
    }
  }
}
```

**stdio transport** (for local dev — Claude Code or Claude Desktop on the same machine):

```json
{
  "mcpServers": {
    "urge": {
      "command": "php",
      "args": ["artisan", "urge:mcp-server", "--user=1"],
      "cwd": "/path/to/urge-v2"
    }
  }
}
```

**Tools:** `get_prompt`, `list_prompts`, `render_prompt`, `save_version`, `store_result`, `get_results`

**Resources:** `urge://prompts`, `urge://prompts/{slug}`, `urge://prompts/{slug}/v/{n}`

See [`documentation/claude-skill.md`](documentation/claude-skill.md) for full API usage examples.

## Testing

```bash
php artisan test    # 185 tests
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan urge:mcp-server` | Start stdio MCP server |
| `php artisan urge:import-v1 {path}` | Migrate data from URGE v1 SQLite database |

## Documentation

- [`documentation/architecture.md`](documentation/architecture.md) — Data model, integration architecture, component hierarchy
- [`documentation/install.md`](documentation/install.md) — Installation and deployment guide
- [`documentation/claude-skill.md`](documentation/claude-skill.md) — API reference for LLM integration
- [`public/openapi.json`](public/openapi.json) — OpenAPI 3.0 specification
