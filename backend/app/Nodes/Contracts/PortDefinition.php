<?php

namespace App\Nodes\Contracts;

class PortDefinition
{
    private array $inputs = [];
    private array $outputs = [];
    
    public static function make(): self
    {
        return new self();
    }
    
    public function input(string $id, string $label, string $type = 'any'): self
    {
        $this->inputs[] = [
            'id' => $id,
            'label' => $label,
            'type' => $type,
        ];
        return $this;
    }
    
    public function output(string $id, string $label, string $type = 'any'): self
    {
        $this->outputs[] = [
            'id' => $id,
            'label' => $label,
            'type' => $type,
        ];
        return $this;
    }
    
    public function defaultInput(string $type = 'any'): self
    {
        return $this->input('input', 'Input', $type);
    }
    
    public function defaultOutput(string $type = 'any'): self
    {
        return $this->output('output', 'Output', $type);
    }
    
    // For condition nodes - multiple named outputs
    public function conditionalOutputs(array $branches): self
    {
        foreach ($branches as $id => $label) {
            $this->outputs[] = [
                'id' => $id,
                'label' => $label,
                'type' => 'any',
            ];
        }
        return $this;
    }
    
    public function getInputs(): array { return $this->inputs; }
    public function getOutputs(): array { return $this->outputs; }
}