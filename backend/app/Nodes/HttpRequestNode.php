<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;
use App\Nodes\Enums\OutputType;
use Illuminate\Support\Facades\Http;

class HttpRequestNode extends BaseNode
{
    private const TIMEOUT = 30;
    
    public function execute(mixed $input, array $data): NodeOutput
    {
        $method = strtoupper($data['method'] ?? 'GET');
        $url = $data['url'] ?? '';
        $headers = $data['headers'] ?? [];
        $body = $data['body'] ?? null;
        
        // Replace placeholders in URL and body with input data
        $url = $this->replacePlaceholders($url, $input);
        
        try {
            $request = Http::timeout(self::TIMEOUT);
            
            // Add headers
            foreach ($headers as $header) {
                if (!empty($header['key']) && !empty($header['value'])) {
                    $request->withHeaders([
                        $header['key'] => $this->replacePlaceholders($header['value'], $input),
                    ]);
                }
            }
            
            // Make request
            $response = match ($method) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $body ? json_decode($this->replacePlaceholders($body, $input), true) : null),
                'PUT' => $request->put($url, $body ? json_decode($this->replacePlaceholders($body, $input), true) : null),
                'PATCH' => $request->patch($url, $body ? json_decode($this->replacePlaceholders($body, $input), true) : null),
                'DELETE' => $request->delete($url),
                default => $request->get($url),
            };
            
            $responseData = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->json() ?? $response->body(),
            ];
            
            \execution_logger()->log($this->executionId, $this->nodeId, 'http_request', [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
            ]);
            
            return new NodeOutput(
                data: $responseData,
                type: OutputType::JSON,
                metadata: ['statusCode' => $response->status()]
            );
            
        } catch (\Exception $e) {
            return NodeOutput::error(
                "HTTP request failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }
    
    private function replacePlaceholders(string $template, mixed $input): string
    {
        if (!is_array($input)) {
            return str_replace('{{input}}', (string) $input, $template);
        }
        
        foreach ($input as $key => $value) {
            $template = str_replace("{{$key}}", is_array($value) ? json_encode($value) : (string) $value, $template);
        }
        
        return $template;
    }
    
    public function validate(array $data): ValidationResult
    {
        $errors = [];
        
        if (empty($data['url'])) {
            $errors[] = 'URL is required';
        } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL) && !str_starts_with($data['url'], 'http')) {
            $errors[] = 'Invalid URL format';
        }
        
        // Check for blocked domains (security)
        $blockedDomains = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        foreach ($blockedDomains as $blocked) {
            if (str_contains($data['url'] ?? '', $blocked)) {
                $errors[] = "Access to {$blocked} is not allowed";
            }
        }
        
        return empty($errors) ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }
    
    public static function type(): string { return 'http_request'; }
    public static function label(): string { return 'HTTP Request'; }
    public static function category(): string { return 'integration'; }
    
    public static function ports(): PortDefinition
    {
        return PortDefinition::make()
            ->defaultInput('any')
            ->output('output', 'Response', 'json')
            ->output('error', 'Error', 'text');
    }
    
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'method',
                    'type' => 'select',
                    'label' => 'Method',
                    'options' => [
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ],
                    'default' => 'GET',
                ],
                [
                    'name' => 'url',
                    'type' => 'text',
                    'label' => 'URL',
                    'placeholder' => 'https://api.example.com/data',
                ],
                [
                    'name' => 'headers',
                    'type' => 'keyvalue',
                    'label' => 'Headers',
                ],
                [
                    'name' => 'body',
                    'type' => 'textarea',
                    'label' => 'Body (JSON)',
                    'placeholder' => '{"key": "value"}',
                    'showWhen' => ['method' => ['POST', 'PUT', 'PATCH']],
                    'language' => 'json',
                ],
            ],
        ];
    }
}