<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = [
        'workflow_id', 
        'node_id', 
        'type', 
        'data', 
        'position'
    ];

    // عشان لما نحفظ الـ Array (data و position) يتحولو لـ JSON تلقائياً في الداتابيز
    protected $casts = [
        'data' => 'array',
        'position' => 'array',
    ];
        public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
