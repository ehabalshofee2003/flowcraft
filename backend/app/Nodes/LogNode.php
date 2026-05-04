<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;
use App\Services\ExecutionLogger;

class LogNode extends BaseNode
{
    public function execute(mixed $input, array $data): NodeOutput
    {
        $text = $data['text'] ?? $input;
        
        // Log to execution log
        \execution_logger()->log($this->executionId, $this->nodeId, 'output', [
            'message' => $text,
        ]);
        
        return $this->textOutput((string) $text);
    }
    
    public function validate(array $data): ValidationResult
    {
        if (empty($data['text']) && empty($data['useInput'])) {
            return ValidationResult::invalid('Text input is required');
        }
        return ValidationResult::valid();
    }
    
    public static function type(): string { return 'log'; }
    public static function label(): string { return 'Log'; }
    
    public static function ports(): PortDefinition
    {
        return PortDefinition::make()
            ->defaultInput()
            ->defaultOutput();
    }
    
    // Frontend configuration schema
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'text',
                    'type' => 'text',
                    'label' => 'Message',
                    'placeholder' => 'Enter text to log...',
                ],
                [
                    'name' => 'useInput',
                    'type' => 'checkbox',
                    'label' => 'Use input data',
                    'default' => false,
                ],
            ],
        ];
    }
}