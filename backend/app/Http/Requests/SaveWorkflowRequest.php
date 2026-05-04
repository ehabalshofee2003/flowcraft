<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nodes' => 'required|array',
            'nodes.*.id' => 'required|string',
            'nodes.*.type' => 'required|string',
            'nodes.*.data' => 'nullable|array',
            'nodes.*.position' => 'nullable|array',
            'edges' => 'required|array',
            'edges.*.source' => 'required|string',
            'edges.*.target' => 'required|string',
        ];
    }
}