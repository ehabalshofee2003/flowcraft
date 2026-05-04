<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\GraphTraverser;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private GraphTraverser $traverser) {}

    public function chat(Request $request)
    {
        $message = $request->input('message', '');
        $messageLower = strtolower(trim($message));

        // 1. كشف النية (Intent Detection) - هل هو بدو يشغل شيء؟
        $wantsToRun = str_contains($messageLower, 'run') || 
                      str_contains($messageLower, 'شغل') || 
                      str_contains($messageLower, 'نفذ') || 
                      str_contains($messageLower, 'شغّل') ||
                      str_contains($messageLower, 'start');

        if ($wantsToRun) {
            // 2. استخراج اسم الأتمتة من الجملة
            // نحذف الكلمات الدلالية عشان نطلع الاسم الصافي
            $name = str_ireplace(['run ', 'run the ', 'شغل ', 'شغّل ', 'نفذ ', 'start ', 'workflow ', 'automation ', 'الأتمتة '], '', $message);
            $name = trim($name);

            if (empty($name)) {
                return response()->json([
                    'reply' => "❌ Please specify the workflow name. Example: 'Run My Workflow'"
                ]);
            }

        // 3. بحث ذكي: إذا كان المدخل رقم، ابحث بالـ ID. إذا نص، ابحث بالاسم
        $workflow = is_numeric($name) 
            ? Workflow::find($name) 
            : Workflow::where('name', $name)->first();

        if (!$workflow) {
            return response()->json([
                'reply' => "❌ I couldn't find any workflow with name or ID **{$name}**."
            ]);
        }

            // 4. تحضير البيانات وتشغيل محرك التنفيذ (نفس المحرك القديم!)
            $nodes = $workflow->nodes->map(fn ($node) => [
                'id' => $node->node_id,
                'type' => $node->type,
                'data' => $node->data
            ])->toArray();

            $edges = $workflow->edges->map(fn ($edge) => [
                'source' => $edge->source,
                'target' => $edge->target,
                'sourceHandle' => $edge->source_handle,
                'targetHandle' => $edge->target_handle,
            ])->toArray();

            // تشغيل الـ Workflow
            $result = $this->traverser->execute($nodes, $edges);

            // 5. إرجاع النتيجة للشات
            if ($result->success) {
                return response()->json([
                    'reply' => "✅ Workflow **{$name}** executed successfully!\n\n💡 **Output:**\n{$result->output}"
                ]);
            } else {
                return response()->json([
                    'reply' => "⚠️ Workflow **{$name}** ran into an error:\n" . implode("\n", $result->errors)
                ]);
            }
        }

        // 6. ردود افتراضية إذا ما فهمت الأمر
        return response()->json([
            'reply' => "🤖 I am FlowCraft Agent. I can run your saved workflows.\n\nTry saying:\n- 'Run [Workflow Name]'\n- 'شغل [اسم الأتمتة]'"
        ]);
    }
}