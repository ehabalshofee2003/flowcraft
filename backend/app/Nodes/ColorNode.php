<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;

class ColorNode extends BaseNode
{
    public function execute(mixed $input, array $data): NodeOutput
    {
        // استخدم !empty عشان يتجاهل النص الفاضي ويأخذ الـ input
        $color = !empty($data['color']) ? $data['color'] : '#000000';
        $text = !empty($data['text']) ? $data['text'] : (string) $input;
        
        $html = sprintf(
            '<span style="color: %s; font-weight: bold; font-size: 16px;">%s</span>',
            htmlspecialchars($color, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8')
        );
        
        return $this->htmlOutput($html);
    }
    
    public function validate(array $data): ValidationResult
    {
        if (empty($data['color'])) {
            return ValidationResult::invalid('Color is required');
        }
        
        // Validate hex color
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'])) {
            return ValidationResult::invalid('Invalid color format (use #RRGGBB)');
        }
        
        return ValidationResult::valid();
    }
    
    public static function type(): string { return 'color'; }
    public static function label(): string { return 'Color'; }
    public static function category(): string { return 'formatting'; }
    
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'text',
                    'type' => 'text',
                    'label' => 'Text',
                    'placeholder' => 'Leave empty to use input...',
                ],
                [
                    'name' => 'color',
                    'type' => 'color',
                    'label' => 'Color',
                    'default' => '#3b82f6',
                ],
            ],
        ];
    }
}