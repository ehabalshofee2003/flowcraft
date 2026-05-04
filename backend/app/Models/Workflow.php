<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
          protected $fillable = ['name'];

    public function nodes()
{
    return $this->hasMany(Node::class);
}

public function edges()
{
    return $this->hasMany(Edge::class);
}
}
