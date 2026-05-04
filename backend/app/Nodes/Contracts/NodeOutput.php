<?php

namespace App\Nodes\Contracts;
use App\Nodes\Enums\OutputType;
class NodeOutput
{
    public function __construct(
        public mixed $data,
        public OutputType $type = OutputType::TEXT,
        public ?string $error = null,
        public array $metadata = []
    ) {}
    
    public static function success(mixed $data, OutputType $type = OutputType::TEXT): self
    {
        return new self(data: $data, type: $type);
    }
    
    public static function error(string $message, mixed $data = null): self
    {
        return new self(data: $data, type: OutputType::ERROR, error: $message);
    }
    
    public function isError(): bool
    {
        return $this->type === OutputType::ERROR;
    }
}