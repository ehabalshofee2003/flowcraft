<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;
use App\Nodes\Enums\OutputType;
use App\Services\ExecutionLogger;

class ConditionNode extends BaseNode
{
    public function execute(mixed $input, array $data): NodeOutput
    {
        // إذا ما اختار Operator، افترض Equals
        $operator = !empty($data['operator']) ? $data['operator'] : 'equals';
        $value = $data['value'] ?? '';
        
        // Handle JSON input
        $compareValue = is_array($input) ? ($input['data'] ?? json_encode($input)) : $input;
        $compareValue = (string) $compareValue;

        $result = match ($operator) {
            'equals' => $compareValue === $value,
            'not_equals' => $compareValue !== $value,
            'contains' => str_contains($compareValue, $value),
            'not_contains' => !str_contains($compareValue, $value),
            'greater_than' => is_numeric($compareValue) && is_numeric($value) && (float) $compareValue > (float) $value,
            'less_than' => is_numeric($compareValue) && is_numeric($value) && (float) $compareValue < (float) $value,
            'is_empty' => empty($compareValue),
            'is_not_empty' => !empty($compareValue),
            'matches_regex' => preg_match($value, $compareValue) === 1,
            default => false,
        };

        \execution_logger()->log($this->executionId, $this->nodeId, 'condition', [
            'input' => $compareValue,
            'operator' => $operator,
            'value' => $value,
            'result' => $result,
        ]);

        return new NodeOutput(
            data: $input,
            type: OutputType::TEXT,
            metadata: [
                'branch' => $result ? 'true' : 'false',
                'conditionResult' => $result,
            ]
        );
    }
    public function validate(array $data): ValidationResult
    {
        $operator = $data['operator'] ?? '';
        $value = $data['value'] ?? '';
        
        $operatorsRequiringValue = ['equals', 'not_equals', 'contains', 'not_contains', 
                                     'greater_than', 'less_than', 'matches_regex'];
        
        if (in_array($operator, $operatorsRequiringValue) && $value === '') {
            return ValidationResult::invalid('Value is required for this operator');
        }
        
        if ($operator === 'matches_regex') {
            // Validate regex
            if (@preg_match($value, '') === false) {
                return ValidationResult::invalid('Invalid regular expression');
            }
        }
        
        return ValidationResult::valid();
    }
    
    public static function type(): string { return 'condition'; }
    public static function label(): string { return 'Condition'; }
    public static function category(): string { return 'logic'; }
    
    public static function ports(): PortDefinition
    {
        return PortDefinition::make()
            ->defaultInput()
            ->conditionalOutputs([
                'true' => 'True',
                'false' => 'False',
            ]);
    }
    
    public static function schema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'operator',
                    'type' => 'select',
                    'label' => 'Operator',
                    'options' => [
                        'equals' => 'Equals',
                        'not_equals' => 'Not equals',
                        'contains' => 'Contains',
                        'not_contains' => 'Not contains',
                        'greater_than' => 'Greater than',
                        'less_than' => 'Less than',
                        'is_empty' => 'Is empty',
                        'is_not_empty' => 'Is not empty',
                        'matches_regex' => 'Matches regex',
                    ],
                    'default' => 'equals',
                ],
                [
                    'name' => 'value',
                    'type' => 'text',
                    'label' => 'Value',
                    'placeholder' => 'Compare value...',
                    'showWhen' => ['operator' => ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'matches_regex']],
                ],
            ],
        ];
    }
}