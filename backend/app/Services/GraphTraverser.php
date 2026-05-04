<?php

namespace App\Services;

use App\Nodes\Contracts\NodeInterface;
use App\Nodes\Contracts\NodeOutput;
use App\Nodes\NodeRegistry;

class GraphTraverser
{
    private NodeRegistry $registry;
    private ExecutionLogger $logger;
    private string $executionId;
    
    public function __construct(NodeRegistry $registry, ExecutionLogger $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }
    
    public function execute(array $nodes, array $edges): ExecutionResult
    {
        $this->executionId = uniqid('exec_');
        $this->logger->start($this->executionId);
        
        // Build adjacency structures
        $nodeMap = $this->buildNodeMap($nodes);
        $adjacencyList = $this->buildAdjacencyList($edges);
        
        // Find entry nodes (no incoming edges)
        $entryNodes = $this->findEntryNodes($nodes, $edges);
        
        if (empty($entryNodes)) {
            return ExecutionResult::error($this->executionId, 'No entry point found in workflow');
        }
        
        // Execute from each entry node
        $results = [];
        $errors = [];
        
        foreach ($entryNodes as $entryNodeId) {
            $result = $this->executePath($entryNodeId, $nodeMap, $adjacencyList, null);
            
            if ($result->isError()) {
                $errors[] = $result->error;
            } else {
                $results[] = $result;
            }
        }
        
        $finalOutput = $this->combineResults($results);
        
        $this->logger->complete(
            $this->executionId,
            empty($errors),
            $finalOutput
        );
        // جلب بيانات التنفيذ بشكل آمن (لتجنب خطأ Null)
        $executionData = $this->logger->getExecution($this->executionId) ?? [];
        
        return new ExecutionResult(
            executionId: $this->executionId,
            success: empty($errors),
            output: $finalOutput,
            errors: $errors,
            logs: $executionData['logs'] ?? [],
            nodeStatuses: $executionData['nodeStatuses'] ?? [],
        );
    }
    
    private function executePath(
        string $nodeId,
        array $nodeMap,
        array $adjacencyList,
        mixed $input
    ): NodeOutput {
        $node = $nodeMap[$nodeId] ?? null;
        
        if (!$node) {
            return NodeOutput::error("Node {$nodeId} not found");
        }
        
        $nodeClass = $this->registry->get($node['type']);
        
        if (!$nodeClass) {
            $this->logger->setNodeStatus($this->executionId, $nodeId, 'error', "Unknown node type: {$node['type']}");
            return NodeOutput::error("Unknown node type: {$node['type']}");
        }
        
        // Validate node
        $nodeInstance = $this->registry->make($node['type'], $nodeId, $this->executionId);
        $validation = $nodeInstance->validate($node['data'] ?? []);
        
        if (!$validation->isValid()) {
            $this->logger->setNodeStatus($this->executionId, $nodeId, 'error', implode(', ', $validation->getErrors()));
            return NodeOutput::error(implode(', ', $validation->getErrors()));
        }
        
        // Execute node
        $this->logger->setNodeStatus($this->executionId, $nodeId, 'running');
        
        try {
            $output = $nodeInstance->execute($input, $node['data'] ?? []);
            
            if ($output->isError()) {
                $this->logger->setNodeStatus($this->executionId, $nodeId, 'error', $output->error);
                return $output;
            }
            
            $this->logger->setNodeStatus($this->executionId, $nodeId, 'completed');
            
            // Determine which output port to follow
            $branch = $output->metadata['branch'] ?? 'output';
            
            // Get next nodes from this output port
            $nextNodes = $adjacencyList[$nodeId][$branch] ?? [];
            
            if (empty($nextNodes)) {
                return $output; // End of path
            }
            
            // Execute all connected paths (for branching)
            $pathResults = [];
            foreach ($nextNodes as $nextNodeId) {
                $pathResults[] = $this->executePath($nextNodeId, $nodeMap, $adjacencyList, $output->data);
            }
            
            // Return first successful result or last error
            foreach ($pathResults as $result) {
                if (!$result->isError()) {
                    return $result;
                }
            }
            
            return end($pathResults) ?? $output;
            
        } catch (\Exception $e) {
            $this->logger->setNodeStatus($this->executionId, $nodeId, 'error', $e->getMessage());
            return NodeOutput::error("Execution error: {$e->getMessage()}");
        }
    }
    
    private function buildNodeMap(array $nodes): array
    {
        $map = [];
        foreach ($nodes as $node) {
            $map[$node['id']] = $node;
        }
        return $map;
    }
    
    private function buildAdjacencyList(array $edges): array
    {
        $list = [];
        
        foreach ($edges as $edge) {
            $source = $edge['source'];
            $target = $edge['target'];
            $sourceHandle = $edge['sourceHandle'] ?? 'output';
            
            if (!isset($list[$source])) {
                $list[$source] = [];
            }
            if (!isset($list[$source][$sourceHandle])) {
                $list[$source][$sourceHandle] = [];
            }
            
            $list[$source][$sourceHandle][] = $target;
        }
        
        return $list;
    }
    
    private function findEntryNodes(array $nodes, array $edges): array
    {
        $targetIds = array_column($edges, 'target');
        
        return array_values(array_filter(
            array_column($nodes, 'id'),
            fn ($id) => !in_array($id, $targetIds)
        ));
    }
    
    private function combineResults(array $results): string
    {
        $outputs = [];
        
        foreach ($results as $result) {
            $data = $result->data;
            
            if (is_array($data)) {
                $outputs[] = json_encode($data, JSON_PRETTY_PRINT);
            } else {
                $outputs[] = (string) $data;
            }
        }
        
        return implode('<br/>', array_filter($outputs));
    }
}