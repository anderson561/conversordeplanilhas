<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'rules',
        'abrasf_version',
        'is_template',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_template' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function conversionJobs()
    {
        return $this->hasMany(ConversionJob::class);
    }
}
