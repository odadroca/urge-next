# URGE v2 — Claude Skill Document

URGE is a prompt registry and version control system. You can use the URGE API to fetch, render, and manage prompt templates.

## Base URL

```
https://urge-next.acordado.org/api/v1
```

## Authentication

All API requests (except health check) require a Bearer token:

```
Authorization: Bearer urge_YOUR_API_KEY
```

## Quick Start

### 1. Check API health

```bash
curl https://urge-next.acordado.org/api/v1/health
```

### 2. List available prompts

```bash
curl -H "Authorization: Bearer urge_YOUR_KEY" \
  https://urge-next.acordado.org/api/v1/prompts
```

### 3. Get a specific prompt

```bash
curl -H "Authorization: Bearer urge_YOUR_KEY" \
  https://urge-next.acordado.org/api/v1/prompts/my-prompt-slug
```

### 4. Render a prompt with variables

```bash
curl -X POST \
  -H "Authorization: Bearer urge_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"variables": {"name": "Claude", "task": "code review"}}' \
  https://urge-next.acordado.org/api/v1/prompts/my-prompt-slug/render
```

### 5. Save a result

```bash
curl -X POST \
  -H "Authorization: Bearer urge_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"version": 1, "response_text": "The LLM output...", "provider_name": "Anthropic", "model_name": "claude-3.5-sonnet"}' \
  https://urge-next.acordado.org/api/v1/prompts/my-prompt-slug/results
```

## Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check (no auth) |
| GET | `/prompts` | List prompts (filter: type, category_id, tag, search) |
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
| PATCH | `/results/{id}` | Update result (rating, starred, notes) |
| POST | `/mcp` | MCP JSON-RPC endpoint |

## MCP Integration

URGE also supports the Model Context Protocol (MCP). Connect Claude Desktop or Claude Code to URGE as an MCP server.

### Available MCP Tools

- **get_prompt** — Fetch a prompt by slug
- **list_prompts** — List/search prompts
- **render_prompt** — Render a template with variables
- **save_version** — Create a new version
- **store_result** — Save an LLM response
- **get_results** — Get results for a prompt

### MCP Resources

- `urge://prompts` — All prompts as JSON
- `urge://prompts/{slug}` — Active version content
- `urge://prompts/{slug}/v/{n}` — Specific version content

### Claude Desktop Configuration (SSE)

Add to `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "urge": {
      "url": "https://urge-next.acordado.org/api/v1/mcp",
      "headers": {
        "Authorization": "Bearer urge_YOUR_API_KEY"
      }
    }
  }
}
```

### Claude Code Configuration (stdio)

For local development, use the stdio transport:

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
