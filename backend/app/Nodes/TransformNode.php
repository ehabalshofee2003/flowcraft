<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\ValidationResult;

class TransformNode extends BaseNode
{
    public function execute(mixed $input, array $data): NodeOutput
    {
        $operation = !empty($data['operation']) ? $data['operation'] : 'uppercase';
        
        // 🛡️ حماية جديدة: إذا الـ Input مصفوفة (جاي من HTTP Request) نحولها لنص JSON
        if (is_array($input)) {
            $value = json_encode($input);
        } else {
            $value = !empty($data['value']) ? (string) $data['value'] : (string) $input;
        }

        $result = match ($operation) {
            'uppercase' => strtoupper($value),
            'lowercase' => strtolower($value),
            'trim' => trim($value),
            'replace' => str_replace($data['search'] ?? '', $data['replace'] ?? '', $value),
            'prepend' => ($data['prefix'] ?? '') . $value,
            'append' => $value . ($data['suffix'] ?? ''),
            'substring' => substr($value, (int) ($data['start'] ?? 0), (int) ($data['length'] ?? null)),
            'json_extract' => $this->extractJson($value, $data['path'] ?? ''),
            'template' => $this->applyTemplate($data['template'] ?? '', $input),
            default => $value,
        };
        
        return $this->textOutput($result);
    }
    private function extractJson(string $json, string $path): string
    {
        $data = json_decode($json, true);
        if (!$data) return $json;
        
        $keys = explode('.', trim($path, '.'));
        foreach ($keys as $key) {
            if (!is_array($data) || !isset($data[$key])) {
                return '';
            }
            $data = $data[$key];
        }
        
        return is_array($data) ? json_encode($data) : (string) $data;
    }
    
    private function applyTemplate(string $template, mixed $input): string
    {
        if (!is_array($input)) {
            return str_replace('{{value}}', (string) $input, $template);
        }
        
        foreach ($input as $key => $value) {
            $template = str_replace("{{{$key}}}", is_array($value) ? json_encode($value) : (string) $value, $template);
        }
        
        return $template;
    }
    
    public function validate(array $data): ValidationResult
    {
        $operation = $data['operation'] ?? '';
        
        if ($operation === 'replace' && empty($data['search'])) {
            return ValidationResult::invalid('Search value is required for replace operation');
        }
        
        return ValidationResult::valid();
    }
    
    public static function type(): string { return 'transform'; }
    public static function label(): string { return 'Transform'; }
    public static function category(): string { return 'transform';
    }
    
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'operation',
                    'type' => 'select',
                    'label' => 'Operation',
                    'options' => [
                        'uppercase' => 'Uppercase',
                        'lowercase' => 'Lowercase',
                        'trim' => 'Trim whitespace',
                        'replace' => 'Find & Replace',
                        'prepend' => 'Prepend text',
                        'append' => 'Append text',
                        'substring' => 'Substring',
                        'json_extract' => 'Extract from JSON',
                        'template' => 'Apply template',
                    ],
                    'default' => 'uppercase',
                ],
                [
                    'name' => 'search',
                    'type' => 'text',
                    'label' => 'Search',
                    'showWhen' => ['operation' => ['replace']],
                ],
                [
                    'name' => 'replace',
                    'type' => 'text',
                    'label' => 'Replace with',
                    'showWhen' => ['operation' => ['replace']],
                ],
                [
                    'name' => 'prefix',
                    'type' => 'text',
                    'label' => 'Prefix',
                    'showWhen' => ['operation' => ['prepend']],
                ],
                [
                    'name' => 'suffix',
                    'type' => 'text',
                    'label' => 'Suffix',
                    'showWhen' => ['operation' => ['append']],
                ],
                [
                    'name' => 'path',
                    'type' => 'text',
                    'label' => 'JSON Path',
                    'placeholder' => 'data.items.0.name',
                    'showWhen' => ['operation' => ['json_extract']],
                ],
                [
                    'name' => 'template',
                    'type' => 'textarea',
                    'label' => 'Template',
                    'placeholder' => 'Hello {{name}}, your order #{{id}} is ready',
                    'showWhen' => ['operation' => ['template']],
                ],
            ],
        ];
    }
}