<?php

namespace App\Services;

class ExecutionResult
{
    public function __construct(
        public string $executionId,
        public bool $success,
        public string $output,
        public array $errors,
        public array $logs,
        public array $nodeStatuses
    ) {}
    
    public static function error(string $executionId, string $message): self
    {
        return new self(
            executionId: $executionId,
            success: false,
            output: '',
            errors: [$message],
            logs: [],
            nodeStatuses: []
        );
    }
    
    public function toArray(): array
    {
        return [
            'executionId' => $this->executionId,
            'success' => $this->success,
            'output' => $this->output,
            'errors' => $this->errors,
            'logs' => $this->logs,
            'nodeStatuses' => $this->nodeStatuses,
        ];
    }
}