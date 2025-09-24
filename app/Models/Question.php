<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Question",
 *     type="object",
 *     title="Question",
 *     description="Schema for a quiz question",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1,
 *         description="Unique ID of the question"
 *     ),
 *     @OA\Property(
 *         property="quiz_id",
 *         type="integer",
 *         example=5,
 *         description="ID of the quiz this question belongs to"
 *     ),
 *     @OA\Property(
 *         property="question_text",
 *         type="string",
 *         maxLength=255,
 *         example="What is the capital of France?",
 *         description="The text of the question"
 *     ),
 *     @OA\Property(
 *         property="question_type",
 *         type="string",
 *         enum={"mcq", "true_false", "short_answer"},
 *         example="mcq",
 *         description="Type of the question"
 *     ),
 *     @OA\Property(
 *         property="points",
 *         type="integer",
 *         minimum=1,
 *         example=5,
 *         description="Number of points awarded for this question"
 *     ),
 *     @OA\Property(
 *         property="time_limit",
 *         type="integer",
 *         minimum=0,
 *         nullable=true,
 *         example=30,
 *         description="Time limit for the question in seconds (nullable)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-09-25T10:15:30Z",
 *         description="Timestamp when the question was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-09-25T12:00:00Z",
 *         description="Timestamp when the question was last updated"
 *     )
 * )
 */


class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'points',
        'time_limit',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'question_id', 'id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
