<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // لسه ما عملنا Auth، فخليناها true
    }

    public function rules(): array
    {
        return [
            'nodes' => 'required|array|min:1',
            'nodes.*.id' => 'required|string',
            'nodes.*.type' => 'required|string',
            'nodes.*.data' => 'nullable|array',
            'edges' => 'required|array',
            'edges.*.source' => 'required|string',
            'edges.*.target' => 'required|string',
        ];
    }
}