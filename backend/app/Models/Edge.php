<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edge extends Model
{
  protected $fillable = [
        'workflow_id', 
        'source', 
        'target', 
        'source_handle', 
        'target_handle'
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
