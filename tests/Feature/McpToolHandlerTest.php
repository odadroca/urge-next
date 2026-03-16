<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\Result;
use App\Models\User;
use App\Services\McpToolHandler;
use App\Services\VersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpToolHandlerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private McpToolHandler $handler;
    private Prompt $prompt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->handler = app(McpToolHandler::class);
        $this->prompt = Prompt::create(['name' => 'MCP Test', 'type' => 'prompt', 'created_by' => $this->user->id]);
        app(VersioningService::class)->createVersion($this->prompt, [
            'content' => 'Hello {{name}}, you are {{role}}.',
        ], $this->user);
    }

    public function test_get_prompt(): void
    {
        $result = $this->handler->callTool('get_prompt', ['slug' => $this->prompt->slug]);

        $this->assertEquals($this->prompt->slug, $result['slug']);
        $this->assertEquals('Hello {{name}}, you are {{role}}.', $result['content']);
        $this->assertEquals(['name', 'role'], $result['variables']);
    }

    public function test_get_prompt_not_found(): void
    {
        $result = $this->handler->callTool('get_prompt', ['slug' => 'nonexistent']);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_list_prompts(): void
    {
        $result = $this->handler->callTool('list_prompts', []);

        $this->assertCount(1, $result);
        $this->assertEquals($this->prompt->slug, $result[0]['slug']);
    }

    public function test_list_prompts_with_type_filter(): void
    {
        Prompt::create(['name' => 'Fragment', 'type' => 'fragment', 'created_by' => $this->user->id]);

        $result = $this->handler->callTool('list_prompts', ['type' => 'fragment']);

        $this->assertCount(1, $result);
        $this->assertEquals('fragment', $result[0]['type']);
    }

    public function test_render_prompt(): void
    {
        $result = $this->handler->callTool('render_prompt', [
            'slug' => $this->prompt->slug,
            'variables' => ['name' => 'Claude', 'role' => 'assistant'],
        ]);

        $this->assertEquals('Hello Claude, you are assistant.', $result['rendered']);
    }

    public function test_save_version(): void
    {
        $result = $this->handler->callTool('save_version', [
            'slug' => $this->prompt->slug,
            'content' => 'Updated: {{name}}',
            'commit_message' => 'Update via MCP',
        ], $this->user);

        $this->assertEquals(2, $result['version_number']);
        $this->assertEquals(['name'], $result['variables']);
    }

    public function test_store_result(): void
    {
        $result = $this->handler->callTool('store_result', [
            'slug' => $this->prompt->slug,
            'version' => 1,
            'response_text' => 'MCP result',
            'provider' => 'Anthropic',
            'model' => 'claude-3.5',
        ], $this->user);

        $this->assertTrue($result['created']);
        $this->assertDatabaseHas('results', [
            'response_text' => 'MCP result',
            'source' => 'mcp',
        ]);
    }

    public function test_get_results(): void
    {
        $version = $this->prompt->versions()->first();
        Result::create([
            'prompt_id' => $this->prompt->id,
            'prompt_version_id' => $version->id,
            'source' => 'mcp',
            'response_text' => 'Result 1',
            'created_by' => $this->user->id,
        ]);

        $result = $this->handler->callTool('get_results', ['slug' => $this->prompt->slug]);

        $this->assertCount(1, $result);
        $this->assertEquals('Result 1', $result[0]['response_text']);
    }

    public function test_unknown_tool(): void
    {
        $result = $this->handler->callTool('unknown_tool', []);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_read_resource_prompts_list(): void
    {
        $result = $this->handler->readResource('urge://prompts');

        $this->assertEquals('application/json', $result['mimeType']);
        $decoded = json_decode($result['text'], true);
        $this->assertCount(1, $decoded);
    }

    public function test_read_resource_prompt_content(): void
    {
        $result = $this->handler->readResource("urge://prompts/{$this->prompt->slug}");

        $this->assertEquals('text/plain', $result['mimeType']);
        $this->assertEquals('Hello {{name}}, you are {{role}}.', $result['text']);
    }

    public function test_read_resource_version_content(): void
    {
        $result = $this->handler->readResource("urge://prompts/{$this->prompt->slug}/v/1");

        $this->assertEquals('text/plain', $result['mimeType']);
        $this->assertEquals('Hello {{name}}, you are {{role}}.', $result['text']);
    }

    public function test_get_tool_definitions(): void
    {
        $tools = $this->handler->getToolDefinitions();

        $this->assertCount(6, $tools);
        $names = array_column($tools, 'name');
        $this->assertContains('get_prompt', $names);
        $this->assertContains('render_prompt', $names);
    }

    public function test_get_server_info(): void
    {
        $info = $this->handler->getServerInfo();

        $this->assertEquals('urge', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
    }
}
