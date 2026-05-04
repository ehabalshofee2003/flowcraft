<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;

class DelayNode extends BaseNode
{
    public function execute(mixed $input, array $data): NodeOutput
    {
        $seconds = (int) ($data['seconds'] ?? 1);
        $seconds = max(0, min($seconds, 300)); // Clamp between 0-300 seconds
        
        if ($seconds > 0) {
            sleep($seconds);
        }
        
        
        \execution_logger()->log($this->executionId, $this->nodeId, 'delay', [
            'seconds' => $seconds,
        ]);
        
        // Pass through input unchanged
        return NodeOutput::success($input);
    }
    
    public function validate(array $data): ValidationResult
    {
        $seconds = (int) ($data['seconds'] ?? 1);
        
        if ($seconds < 0 || $seconds > 300) {
            return ValidationResult::invalid('Delay must be between 0 and 300 seconds');
        }
        
        return ValidationResult::valid();
    }
    
    public static function type(): string { return 'delay'; }
    public static function label(): string { return 'Delay'; }
    public static function category(): string { return 'utility'; }
    
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'seconds',
                    'type' => 'number',
                    'label' => 'Seconds',
                    'default' => 1,
                    'min' => 0,
                    'max' => 300,
                ],
            ],
        ];
    }
}