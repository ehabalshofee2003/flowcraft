<?php

namespace App\Nodes;

use App\Nodes\Contracts\NodeInterface;
use Illuminate\Support\Collection;

class NodeRegistry
{
    private array $nodes = [];
    
    public function __construct()
    {
        $this->registerDefaultNodes();
    }
    
    private function registerDefaultNodes(): void
    {
        $this->register(LogNode::class);
        $this->register(ColorNode::class);
        $this->register(ConditionNode::class);
        $this->register(HttpRequestNode::class);
        $this->register(DelayNode::class);
        $this->register(TransformNode::class);
    }
    
    public function register(string $nodeClass): self
    {
        if (!is_subclass_of($nodeClass, NodeInterface::class)) {
            throw new \InvalidArgumentException("{$nodeClass} must implement NodeInterface");
        }
        
        $this->nodes[$nodeClass::type()] = $nodeClass;
        return $this;
    }
    
    public function get(string $type): ?string
    {
        return $this->nodes[$type] ?? null;
    }
    
    public function make(string $type, string $nodeId, string $executionId): ?NodeInterface
    {
        $class = $this->get($type);
        
        if (!$class) {
            return null;
        }
        
        $node = new $class();
        
        if (method_exists($node, 'setContext')) {
            $node->setContext($nodeId, $executionId);
        }
        
        return $node;
    }
    
    public function has(string $type): bool
    {
        return isset($this->nodes[$type]);
    }
    
    public function all(): Collection
    {
        return collect($this->nodes);
    }
    
    public function groupedByCategory(): Collection
    {
        return $this->all()
            ->mapWithKeys(fn (string $class, string $type) => [
                $type => [
                    'type' => $type,
                    'label' => $class::label(),
                    'category' => $class::category(),
                    'ports' => [
                        'inputs' => $class::ports()->getInputs(),
                        'outputs' => $class::ports()->getOutputs(),
                    ],
                    'schema' => method_exists($class, 'schema') ? $class::schema() : [],
                ],
            ])
            ->groupBy('category')
            ->map(fn (Collection $items) => $items->values()->keyBy('type')) 
            ->sortKeys();
    }
    
    public function getFrontendConfig(): array
    {
        return $this->groupedByCategory()->toArray();
    }
}