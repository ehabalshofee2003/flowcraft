<?php

namespace App\Nodes\Contracts;

class ValidationResult
{
    private array $errors = [];
    
    public static function valid(): self
    {
        return new self();
    }
    
    public static function invalid(string|array $errors): self
    {
        $result = new self();
        $result->errors = is_array($errors) ? $errors : [$errors];
        return $result;
    }
    
    public function isValid(): bool { return empty($this->errors); }
    public function getErrors(): array { return $this->errors; }
}