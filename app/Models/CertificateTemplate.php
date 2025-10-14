<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'html_content',
        'is_active',
    ];
}
