<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sectionable extends Model
{
    use HasFactory;

     protected $fillable = [
        'section_id',
        'sectionable_id',
        'sectionable_type',
        'order',
    ];

      public function sectionable()
    {
        return $this->morphTo();
    }

    
}
