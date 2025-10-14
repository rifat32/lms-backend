<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Option extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'explanation',
        'image',
        'matching_pair_text',
        'matching_pair_image',
    ];


    public function getMatchingPairImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $folder_path = "business_1/question_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }
}
