<?php

/**
 * MCP HTTP Client for integration testing.
 *
 * Implements JSON-RPC 2.0 protocol for communicating with WordPress MCP adapter.
 *
 * @package FAWpmcp\Tests\Integration\Support
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Support;

use RuntimeException;

/**
 * HTTP client for MCP JSON-RPC protocol with session management.
 */
class McpClient
{
    private string $baseUrl;
    private string $endpoint;
    private string $username;
    private string $password;
    private ?string $sessionId = null;
    private int $requestId = 0;

    /**
     * Constructor.
     *
     * @param string $baseUrl  Base URL (e.g., 'http://localhost').
     * @param string $endpoint MCP endpoint path (e.g., '/wp-json/mcp/mcp-adapter-default-server').
     * @param string $username Basic Auth username.
     * @param string $password Basic Auth password (application password).
     */
    public function __construct(
        string $baseUrl,
        string $endpoint,
        string $username,
        string $password
    ) {
        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Create client from test environment constants.
     *
     * @return self
     */
    public static function fromEnvironment(): self
    {
        return new self(
            \MCP_TEST_BASE_URL,
            \MCP_TEST_ENDPOINT,
            \MCP_TEST_USERNAME,
            \MCP_TEST_PASSWORD
        );
    }

    /**
     * Initialize MCP session.
     *
     * Must be called before making tool calls.
     *
     * @return array{protocolVersion: string, capabilities: array, serverInfo: array}
     * @throws RuntimeException If initialization fails.
     */
    public function initialize(): array
    {
        $response = $this->sendRequest('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => new \stdClass(),
            'clientInfo'      => [
                'name'    => 'fa-wpmcp-integration-tests',
                'version' => '1.0.0',
            ],
        ], true);

        return $response;
    }

    /**
     * List available tools.
     *
     * @return array{tools: array}
     * @throws RuntimeException If request fails.
     */
    public function listTools(): array
    {
        $this->ensureSession();

        return $this->sendRequest('tools/list', []);
    }

    /**
     * Call an MCP tool.
     *
     * @param string $toolName  Tool name (e.g., 'fa-wpmcp-create-post').
     * @param array  $arguments Tool arguments.
     * @return array Tool response.
     * @throws RuntimeException If call fails.
     */
    public function callTool(string $toolName, array $arguments = []): array
    {
        $this->ensureSession();

        return $this->sendRequest('tools/call', [
            'name'      => $toolName,
            'arguments' => empty($arguments) ? new \stdClass() : $arguments,
        ]);
    }

    /**
     * Get current session ID.
     *
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Close the session (cleanup).
     *
     * @return void
     */
    public function close(): void
    {
        $this->sessionId = null;
        $this->requestId = 0;
    }

    /**
     * Ensure session is initialized.
     *
     * @throws RuntimeException If no session exists.
     */
    private function ensureSession(): void
    {
        if ($this->sessionId === null) {
            throw new RuntimeException(
                'MCP session not initialized. Call initialize() first.'
            );
        }
    }

    /**
     * Send JSON-RPC request.
     *
     * @param string $method        JSON-RPC method.
     * @param array  $params        Method parameters.
     * @param bool   $captureSession Whether to capture session ID from response headers.
     * @return array Response result.
     * @throws RuntimeException If request fails.
     */
    private function sendRequest(string $method, array $params, bool $captureSession = false): array
    {
        $this->requestId++;

        $payload = [
            'jsonrpc' => '2.0',
            'id'      => $this->requestId,
            'method'  => $method,
            'params'  => empty($params) ? new \stdClass() : $params,
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            'Content-Type: application/json',
        ];

        if ($this->sessionId !== null) {
            $headers[] = 'Mcp-Session-Id: ' . $this->sessionId;
        }

        $ch = curl_init($this->baseUrl . $this->endpoint);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => $captureSession,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            throw new RuntimeException('cURL error: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($captureSession) {
            $headerSize = strpos($response, "\r\n\r\n");
            if ($headerSize === false) {
                throw new RuntimeException('Invalid response format');
            }

            $headerText = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize + 4);

            // Extract session ID from headers.
            if (preg_match('/Mcp-Session-Id:\s*([^\r\n]+)/i', $headerText, $matches)) {
                $this->sessionId = trim($matches[1]);
            }

            $response = $body;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Invalid JSON response: ' . json_last_error_msg() . "\nResponse: " . substr($response, 0, 500)
            );
        }

        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? 'Unknown error';
            throw new RuntimeException(
                "HTTP {$httpCode}: {$errorMsg}"
            );
        }

        if (isset($decoded['error'])) {
            throw new RuntimeException(
                'JSON-RPC error: ' . ($decoded['error']['message'] ?? 'Unknown error')
            );
        }

        return $decoded['result'] ?? [];
    }
}
