<?php

namespace App\Http\Controllers;

use App\Services\McpToolHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpController
{
    public function __construct(private McpToolHandler $handler) {}

    /**
     * POST /api/v1/mcp — Handle JSON-RPC 2.0 requests.
     */
    public function handle(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        $method = $body['method'] ?? '';
        $id = $body['id'] ?? null;
        $params = $body['params'] ?? [];

        $result = match ($method) {
            'initialize' => [
                'protocolVersion' => '2024-11-05',
                'capabilities'    => [
                    'tools'     => ['listChanged' => false],
                    'resources' => ['subscribe' => false, 'listChanged' => false],
                ],
                'serverInfo' => $this->handler->getServerInfo(),
            ],
            'tools/list' => [
                'tools' => $this->handler->getToolDefinitions(),
            ],
            'tools/call' => $this->handleToolCall($params, $request),
            'resources/list' => [
                'resources' => $this->handler->getResourceDefinitions(),
            ],
            'resources/read' => $this->handleResourceRead($params),
            'ping' => new \stdClass(),
            default => null,
        };

        if ($result === null) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => [
                    'code'    => -32601,
                    'message' => "Method not found: {$method}",
                ],
            ]);
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ]);
    }

    /**
     * GET /api/v1/mcp — SSE endpoint for MCP streaming.
     */
    public function stream(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () use ($request) {
            // Send initial endpoint message
            $baseUrl = url('/api/v1/mcp');
            echo "event: endpoint\n";
            echo "data: {$baseUrl}\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Keep connection alive with periodic pings
            $timeout = 120;
            $start = time();
            while ((time() - $start) < $timeout) {
                if (connection_aborted()) {
                    break;
                }
                echo ": ping\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                sleep(15);
            }
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function handleToolCall(array $params, Request $request): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        $user = $request->user();

        $result = $this->handler->callTool($toolName, $arguments, $user);

        if (isset($result['error'])) {
            return [
                'content' => [
                    ['type' => 'text', 'text' => $result['error']],
                ],
                'isError' => true,
            ];
        }

        return [
            'content' => [
                ['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)],
            ],
        ];
    }

    private function handleResourceRead(array $params): array
    {
        $uri = $params['uri'] ?? '';
        $resource = $this->handler->readResource($uri);

        if (isset($resource['error'])) {
            return ['contents' => []];
        }

        return [
            'contents' => [$resource],
        ];
    }
}
