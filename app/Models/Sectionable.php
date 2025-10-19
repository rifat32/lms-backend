<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sectionable extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

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

    public function isLesson(): bool
    {
        return $this->sectionable_type === Lesson::class;
    }

    public function isQuiz(): bool
    {
        return $this->sectionable_type === Quiz::class;
    }

    public function getOrderAttribute($value) {

        if(empty($value)) {
return 1;
        }
        return $value;
    }
}
