<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ExecutionLogger
{
    private string $executionId;
    private array $logs = [];
    
    public function start(string $executionId): self
    {
        $this->executionId = $executionId;
        $this->logs = [];
        
        Cache::put("execution:{$executionId}", [
            'id' => $executionId,
            'startedAt' => now()->toISOString(),
            'logs' => [],
            'nodeStatuses' => [],
        ], now()->addHours(1));
        
        return $this;
    }
    
    public function log(string $executionId, string $nodeId, string $type, array $data): void
    {
        $log = [
            'timestamp' => now()->toISOString(),
            'nodeId' => $nodeId,
            'type' => $type,
            'data' => $data,
        ];
        
        $cacheKey = "execution:{$executionId}";
        $execution = Cache::get($cacheKey, ['logs' => []]);
        $execution['logs'][] = $log;
        Cache::put($cacheKey, $execution, now()->addHours(1));
    }
    
    public function setNodeStatus(string $executionId, string $nodeId, string $status, ?string $message = null): void
    {
        $cacheKey = "execution:{$executionId}";
        $execution = Cache::get($cacheKey, ['nodeStatuses' => []]);
        $execution['nodeStatuses'][$nodeId] = [
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];
        Cache::put($cacheKey, $execution, now()->addHours(1));
    }
    
    public function getExecution(string $executionId): ?array
    {
        return Cache::get("execution:{$executionId}");
    }
    
    public function complete(string $executionId, bool $success, ?string $finalOutput = null): void
    {
        $cacheKey = "execution:{$executionId}";
        $execution = Cache::get($cacheKey, []);
        $execution['completedAt'] = now()->toISOString();
        $execution['success'] = $success;
        $execution['finalOutput'] = $finalOutput;
        Cache::put($cacheKey, $execution, now()->addHours(1));
    }
}
