<?php

namespace App\Http\Controllers;
 
use App\Http\Requests\RunWorkflowRequest;
use App\Http\Requests\SaveWorkflowRequest;
use App\Models\Workflow;
use App\Nodes\NodeRegistry;
use App\Services\GraphTraverser;
use App\Services\WorkflowValidator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkflowController extends Controller
{
    // 1. تعريف المتغيرات بأسماء موحدة ومختصرة
    protected $validator;
    protected $traverser;
    protected $registry;

    // 2. حقن كل الخدمات التي يحتاجها هذا الكنترولر
    public function __construct(
        WorkflowValidator $validator,
        GraphTraverser $traverser,
        NodeRegistry $registry
    ) {
        $this->validator = $validator;
        $this->traverser = $traverser;
        $this->registry = $registry;
    }

    
    public function run(RunWorkflowRequest $request): JsonResponse
    {
        $nodes = $request->input('nodes', []);
        $edges = $request->input('edges', []);
        
        // Validate workflow structure
        $validation = $this->validator->validate($nodes, $edges);
        
        if (!$validation->isValid()) {
            return response()->json([
                'success' => false,
                'errors' => $validation->getErrors(),
            ], 422);
        }
        
        // Execute workflow
        $result = $this->traverser->execute($nodes, $edges);
        
        return response()->json($result->toArray());
    }
    
    public function save(SaveWorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::create([
            'name' => $request->input('name'),
        ]);        
        $nodes = $request->input('nodes', []);
        $edges = $request->input('edges', []);
        
        // Store nodes
        foreach ($nodes as $node) {
            $workflow->nodes()->create([
                'node_id' => $node['id'],
                'type' => $node['type'],
                'data' => $node['data'] ?? [],
            ]);
        }
        
        // Store edges
        foreach ($edges as $edge) {
            $workflow->edges()->create([
                'source' => $edge['source'],
                'target' => $edge['target'],
                'source_handle' => $edge['sourceHandle'] ?? null,
                'target_handle' => $edge['targetHandle'] ?? null,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'workflow_id' => $workflow->id,
        ], 201);
    }
    
    public function load(Workflow $workflow): JsonResponse
    {
        $nodes = $workflow->nodes->map(fn ($node) => [
            'id' => $node->node_id,
            'type' => $node->type,
            'data' => $node->data,
            'position' => $node->position ?? ['x' => 0, 'y' => 0],
        ])->values()->toArray();
        
        $edges = $workflow->edges->map(fn ($edge) => [
            'id' => $edge->id,
            'source' => $edge->source,
            'target' => $edge->target,
            'sourceHandle' => $edge->source_handle,
            'targetHandle' => $edge->target_handle,
        ])->values()->toArray();
        
        return response()->json([
            'id' => $workflow->id,
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }

    public function runStream(RunWorkflowRequest $request)
    {
        $validated = $request->validated();

        // التأكد من صحة الرسم البياني قبل بدء التدفق
        $validationResult = $this->validator->validate($validated['nodes'], $validated['edges']);
        
        if (!$validationResult->isValid()) {
            return response()->json(['success' => false, 'errors' => $validationResult->getErrors()], 422);
        }

        // إنشاء استجابة تدفقية (Streamed Response)
        return new StreamedResponse(function() use ($validated) {
            // 1. إعداد رؤوس SSE للتواصل مع المتصفح
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            
            if (ob_get_level() > 0) {
                ob_end_flush();
            }

            // 2. دالة مساعدة لإرسال حدث واحد للواجهة
            $sendEvent = function($event, $data = []) {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                flush();
            };

            try {
                // إخبار الواجهة أن التنفيذ بدأ
                $sendEvent('workflow_start', ['message' => 'Execution started']);

                // (كود مؤقت للتأكد أن الاتصال شغال - سنستبدله بالـ Traverser الحقيقي في الخطوة القادمة)
                $nodes = $validated['nodes'];
                foreach($nodes as $node) {
                    $sendEvent('node_status', [
                        'node_id' => $node['id'],
                        'status' => 'running'
                    ]);
                    sleep(1); // محاكاة العمل

                    $sendEvent('node_status', [
                        'node_id' => $node['id'],
                        'status' => 'success'
                    ]);
                }

                // إخبار الواجهة أن التنفيذ انتهى بنجاح
                $sendEvent('workflow_end', ['success' => true, 'output' => 'Mock execution finished!']);

            } catch (\Throwable $e) {
                // في حال فشل التنفيذ بالكامل
                $sendEvent('workflow_error', ['message' => $e->getMessage()]);
            }
        });
    }

    public function nodeTypes(): JsonResponse
    {
        return response()->json([
            'nodes' => $this->registry->getFrontendConfig(),
        ]);
    }
}