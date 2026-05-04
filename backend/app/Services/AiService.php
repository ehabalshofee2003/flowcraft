<?php

namespace App\Services;

class AiService
{
    // معالجة ذكية بدون الحاجة لانترنت أو API Keys
    public function generateWorkflow(string $userMessage): array
    {
        $message = strtolower($userMessage);
        $hasLog = str_contains($message, 'لوج') || str_contains($message, 'طباعة') || str_contains($message, 'log');
        $hasColor = str_contains($message, 'لون') || str_contains($message, 'color');
        $hasUpper = str_contains($message, 'كبير') || str_contains($message, 'uppercase') || str_contains($message, 'تحويل');
        $hasCondition = str_contains($message, 'شرط') || str_contains($message, 'condition') || str_contains($message, 'إذا');
        $hasHttp = str_contains($message, 'ابي') || str_contains($message, 'api') || str_contains($message, 'http') || str_contains($message, 'جلب');

        $nodes = [];
        $edges = [];
        $lastNodeId = "node_0";

        // 1. إذا طلب جلب API
        if ($hasHttp) {
            $nodes[] = ["id" => "node_1", "type" => "http_request", "data" => ["method" => "GET", "url" => "https://jsonplaceholder.typicode.com/users/1"], "position" => ["x" => 250, "y" => 100]];
            $nodes[] = ["id" => "node_2", "type" => "transform", "data" => ["operation" => "json_extract", "path" => "body.name", "value" => ""], "position" => ["x" => 550, "y" => 100]];
            // إضافة نود Log في النهاية عشان يطبع النتيجة على الشاشة
             
            $edges[] = ["source" => "node_1", "target" => "node_2", "sourceHandle" => null];
             
            // منع إضافة نقطة بداية إضافية لأننا بنيناها هنا
            $isStartingNodeAdded = true; 
        }

        // 2. إذا طلب تحويل (Uppercase)
        if ($hasUpper) {
            $id = $lastNodeId === "node_0" ? "node_1" : "node_3";
            $nodes[] = ["id" => $id, "type" => "transform", "data" => ["operation" => "uppercase", "value" => ""], "position" => ["x" => 550, "y" => 50]];
            if ($lastNodeId !== "node_0") $edges[] = ["source" => $lastNodeId, "target" => $id, "sourceHandle" => null];
            $lastNodeId = $id;
        }

        // 3. إذا طلب تلوين (Color)
        if ($hasColor) {
            $id = ($lastNodeId === "node_0") ? "node_1" : (($lastNodeId === "node_1" || $lastNodeId === "node_2") ? "node_3" : "node_4");
            $color = str_contains($message, 'أحمر') ? '#ef4444' : (str_contains($message, 'أخضر') ? '#22c55e' : '#3b82f6');
            $nodes[] = ["id" => $id, "type" => "color", "data" => ["text" => "", "color" => $color], "position" => ["x" => 850, "y" => 50]];
            if ($lastNodeId !== "node_0") $edges[] = ["source" => $lastNodeId, "target" => $id, "sourceHandle" => null];
            $lastNodeId = $id;
        }

        // 4. إذا طلب شرط (Condition)
        if ($hasCondition) {
            $nodes[] = ["id" => "node_1", "type" => "log", "data" => ["text" => "admin"], "position" => ["x" => 250, "y" => 100]];
            $nodes[] = ["id" => "node_2", "type" => "condition", "data" => ["operator" => "equals", "value" => "admin"], "position" => ["x" => 550, "y" => 100]];
            $nodes[] = ["id" => "node_3", "type" => "color", "data" => ["text" => "Access Granted", "color" => "#22c55e"], "position" => ["x" => 850, "y" => 20]];
            $nodes[] = ["id" => "node_4", "type" => "color", "data" => ["text" => "Access Denied", "color" => "#ef4444"], "position" => ["x" => 850, "y" => 180]];
            return $this->successResponse("تم إنشاء مسار تفرع شرطي (True / False).", $nodes, [
                ["source" => "node_1", "target" => "node_2", "sourceHandle" => null],
                ["source" => "node_2", "target" => "node_3", "sourceHandle" => "true"],
                ["source" => "node_2", "target" => "node_4", "sourceHandle" => "false"]
            ]);
        }

        // 5. إذا طلب لوج (Log) أو لم يفهم شيء
        // 5. التأكد من وجود "نقطة بداية" (Log Node) دائماً
        // إذا كان هناك نودات ولكنها ليست نقطة بداية (مثل HTTP أو Log)، أضف Log في البداية
        $isStartingNodeAdded = ($hasLog || $hasHttp);
        
        if (!$isStartingNodeAdded && !empty($nodes)) {
            // أضف نود Log في البداية
            array_unshift($nodes, ["id" => "node_start", "type" => "log", "data" => ["text" => "hello world"], "position" => ["x" => 50, "y" => 50]]);
            
            // غيّر اسم أول نود كان موجود ليصبح node_2
            if (isset($nodes[1]['id'])) {
                $oldFirstNodeId = $nodes[1]['id'];
                $nodes[1]['id'] = "node_2";
                $nodes[1]['position']['x'] = 350;
                
                // حدّت الأسهم لكي تتصل بالنود الجديدة
                foreach ($edges as &$edge) {
                    if ($edge['source'] === $oldFirstNodeId) {
                        $edge['source'] = "node_2";
                    }
                }
                // أضف سهم جديد يربط نقطة البداية بالنود التالية
                array_unshift($edges, ["source" => "node_start", "target" => "node_2", "sourceHandle" => null]);
            }
        } 
        // أما إذا طلب المستخدم لوج فقط، أو لم يطلب شي
        elseif ($hasLog || empty($nodes)) {
            $nodes[] = ["id" => "node_1", "type" => "log", "data" => ["text" => "Hello from AI"], "position" => ["x" => 250, "y" => 50]];
        }    

        return $this->successResponse("تم إنشاء سلسلة العمليات بنجاح.", $nodes, $edges);
    }

    // دالة مساعدة لتنسيق الرد الجاهز
    private function successResponse(string $explanation, array $nodes, array $edges): array
    {
        return [
            'error' => null,
            'explanation' => $explanation,
            'workflow' => [
                'explanation' => $explanation,
                'nodes' => $nodes,
                'edges' => $edges
            ]
        ];
    }
}