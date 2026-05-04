<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AiChatController;

Route::post('/workflow/save', [WorkflowController::class, 'save']);
Route::get('/workflow/{id}', [WorkflowController::class, 'load']);
Route::post('/workflow/run', [WorkflowController::class, 'run']);
Route::get('/node-types', [WorkflowController::class, 'nodeTypes']); // <-- هاد الجديد
Route::post('/workflow/run-stream', [WorkflowController::class, 'runStream']);
Route::post('/chat', [ChatController::class, 'chat']);
Route::post('/ai/chat', [AiChatController::class, 'chat']);