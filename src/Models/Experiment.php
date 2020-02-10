<?php

namespace DustinAP\AbTesting\Models;

use Illuminate\Database\Eloquent\Model;

class Experiment extends Model {
    protected $table = 'ab_experiments';

    protected $fillable = [
        'name',
        'url',
        'visitors',
    ];

    protected $casts = [
        'visitors' => 'integer',
    ];

    public function goals() {
        return $this->hasMany(Goal::class);
    }

    public function incrementVisitor() {
        $this->increment('visitors');
    }
}
