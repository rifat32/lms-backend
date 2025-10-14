<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'enrollment_id',
        'certificate_code',
        'issued_at',
        'pdf_url',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'id');
    }
}
