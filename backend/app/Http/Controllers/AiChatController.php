<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function chat(Request $request, AiService $aiService): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $result = $aiService->generateWorkflow($request->message);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 500);
        }

        return response()->json([
            'explanation' => $result['explanation'],
            'workflow' => $result['workflow']
        ]);
    }
}