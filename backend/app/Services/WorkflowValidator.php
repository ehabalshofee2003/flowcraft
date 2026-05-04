<?php

namespace App\Services;

use App\Nodes\NodeRegistry;
use App\Nodes\Contracts\ValidationResult;
class WorkflowValidator
{
    private NodeRegistry $registry;
    
    public function __construct(NodeRegistry $registry)
    {
        $this->registry = $registry;
    }
    
    public function validate(array $nodes, array $edges): ValidationResult
    {
        $errors = [];
        
        // Check for empty workflow
        if (empty($nodes)) {
            $errors[] = 'Workflow must have at least one node';
        }
        
        // Check for unknown node types
        foreach ($nodes as $node) {
            if (!$this->registry->has($node['type'])) {
                $errors[] = "Unknown node type: {$node['type']}";
            }
        }
        
        // Check for disconnected nodes (warning, not error)
        $connectedNodes = $this->getConnectedNodes($edges);
        $disconnectedNodes = array_diff(
            array_column($nodes, 'id'),
            $connectedNodes
        );
        
        // Check for entry points
        $entryNodes = $this->findEntryNodes($nodes, $edges);
        if (empty($entryNodes) && !empty($nodes)) {
            $errors[] = 'Workflow must have at least one entry point (node with no incoming connections)';
        }
        
        // Validate individual nodes
        foreach ($nodes as $node) {
            $nodeClass = $this->registry->get($node['type']);
            if ($nodeClass) {
                $nodeInstance = new $nodeClass();
                $validation = $nodeInstance->validate($node['data'] ?? []);
                
                if (!$validation->isValid()) {
                    foreach ($validation->getErrors() as $nodeError) {
                        $errors[] = "Node '{$node['id']}' ({$nodeClass::label()}): {$nodeError}";
                    }
                }
            }
        }
        
        // Check for cycles (potential infinite loop)
        if ($this->hasCycle($nodes, $edges)) {
            $errors[] = 'Workflow contains a cycle (loops are not supported)';
        }
        
        return new ValidationResult($errors);
    }
    
    private function getConnectedNodes(array $edges): array
    {
        $nodes = [];
        foreach ($edges as $edge) {
            $nodes[] = $edge['source'];
            $nodes[] = $edge['target'];
        }
        return array_unique($nodes);
    }
    
    private function findEntryNodes(array $nodes, array $edges): array
    {
        $targetIds = array_column($edges, 'target');
        
        return array_values(array_filter(
            array_column($nodes, 'id'),
            fn ($id) => !in_array($id, $targetIds)
        ));
    }
    
    private function hasCycle(array $nodes, array $edges): bool
    {
        $adjacencyList = [];
        foreach ($edges as $edge) {
            $adjacencyList[$edge['source']][] = $edge['target'];
        }
        
        $visited = [];
        $recursionStack = [];
        
        foreach (array_column($nodes, 'id') as $nodeId) {
            if ($this->detectCycleDFS($nodeId, $adjacencyList, $visited, $recursionStack)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectCycleDFS(
        string $nodeId,
        array $adjacencyList,
        array &$visited,
        array &$recursionStack
    ): bool {
        if (!isset($visited[$nodeId])) {
            $visited[$nodeId] = true;
            $recursionStack[$nodeId] = true;
            
            foreach ($adjacencyList[$nodeId] ?? [] as $neighbor) {
                if (!isset($visited[$neighbor]) && $this->detectCycleDFS($neighbor, $adjacencyList, $visited, $recursionStack)) {
                    return true;
                } elseif (isset($recursionStack[$neighbor]) && $recursionStack[$neighbor]) {
                    return true;
                }
            }
        }
        
        $recursionStack[$nodeId] = false;
        return false;
    }
}