<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Upload extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'file_path',
        'original_name',
        'mime_type',
        'xml_type',
        'acumulador',
        'excel_output_type',
        'provider_endereco',
        'provider_bairro',
        'provider_cep',
        'provider_municipio',
        'provider_uf',
        'provider_fone',
        'starting_number',
        'size_bytes',
        'status',
        'meta_data',
        'file_hash',
        'provider_info',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'provider_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function conversionJobs()
    {
        return $this->hasMany(ConversionJob::class);
    }

    public function latestConversionJob()
    {
        return $this->hasOne(ConversionJob::class)->latestOfMany();
    }
}
