<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeInterface;
use App\Nodes\Contracts\NodeOutput;
use App\Nodes\Contracts\PortDefinition;
use App\Nodes\Contracts\ValidationResult;
use App\Nodes\Enums\OutputType;

abstract class BaseNode implements NodeInterface
{
    protected ?string $nodeId = null;
    protected ?string $executionId = null;
    
    public function setContext(string $nodeId, string $executionId): self
    {
        $this->nodeId = $nodeId;
        $this->executionId = $executionId;
        return $this;
    }
    
    public function validate(array $data): ValidationResult
    {
        return ValidationResult::valid();
    }
    
    public static function category(): string
    {
        return 'general';
    }
    
    public static function ports(): PortDefinition
    {
        return PortDefinition::make()
            ->defaultInput()
            ->defaultOutput();
    }
    
    protected function textOutput(string $text): NodeOutput
    {
        return NodeOutput::success($text, OutputType::TEXT);
    }
    
    protected function htmlOutput(string $html): NodeOutput
    {
        return NodeOutput::success($html, OutputType::HTML);
    }
    
    protected function jsonOutput(array $data): NodeOutput
    {
        return NodeOutput::success($data, OutputType::JSON);
    }
    
    protected function voidOutput(): NodeOutput
    {
        return NodeOutput::success(null, OutputType::VOID);
    }
}