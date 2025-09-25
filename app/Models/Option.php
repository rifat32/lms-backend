<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Option",
 *     type="object",
 *     title="Option Model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="question_id", type="integer", example=10),
 *     @OA\Property(property="text", type="string", example="Option A"),
 *     @OA\Property(property="is_correct", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="OptionRequest",
 *     type="object",
 *     required={"question_id", "text", "is_correct"},
 *     @OA\Property(property="id", type="integer", description="Required for update", example=1),
 *     @OA\Property(property="question_id", type="integer", example=10),
 *     @OA\Property(property="text", type="string", example="Option A"),
 *     @OA\Property(property="is_correct", type="boolean", example=true)
 * )
 */



class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
    ];
}
