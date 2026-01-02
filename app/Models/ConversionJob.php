<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversionJob extends Model
{
    use HasFactory;
    protected $fillable = [
        'upload_id',
        'mapping_id',
        'status',
        'result_file_path',
        'error_log',
        'started_at',
        'completed_at',
        'xml_type',
        'state',
        'starting_number',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public function mapping()
    {
        return $this->belongsTo(Mapping::class);
    }
}
