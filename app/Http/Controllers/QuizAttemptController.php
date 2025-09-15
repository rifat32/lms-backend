<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizAttemptController extends Controller
{
    // POST /api/quizzes/{id}/attempts
    public function store(Request $request, $id)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,question_id',
            'answers.*.user_answer' => 'required'
        ]);

        $user = Auth::user();

        $quiz_attempt = QuizAttempt::create([
            'quiz_id' => $id,
            'user_id' => $user->id,
            'score' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $score = 0;
        $feedback = [];

        foreach ($request->answers as $answer) {
            $question = Question::findOrFail($answer['question_id']);

            $is_correct = false;
            if($question->question_type !== 'essay') {
                // For non-essay, check if answer matches correct option
                $correct_option = $question->options()->where('is_correct', true)->first();
                $is_correct = $correct_option && $correct_option->id == $answer['user_answer'];
                $score += $is_correct ? $question->points : 0;
            } else {
                $feedback[] = [
                    'question_id' => $question->question_id,
                    'user_answer' => $answer['user_answer'],
                    'message' => 'Requires manual grading'
                ];
            }
        }

        $quiz_attempt->score = $score;
        $quiz_attempt->is_passed = $score >= 50; // example passing score
        $quiz_attempt->save();

        return response()->json([
            'attempt_id' => $quiz_attempt->id,
            'score' => $quiz_attempt->score,
            'is_passed' => $quiz_attempt->is_passed,
            'feedback' => $feedback,
        ], 201);
    }

    // PUT /api/quiz-attempts/{id}/grade (Admin)
    public function grade(Request $request, $id)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,question_id',
            'score' => 'required|numeric|min:0'
        ]);

        $quiz_attempt = QuizAttempt::findOrFail($id);

        // Add manual score to existing score
        $quiz_attempt->score += $request->score;
        $quiz_attempt->save();

        return response()->json([
            'attempt_id' => $quiz_attempt->id,
            'message' => 'Grade updated.',
            'new_score' => $quiz_attempt->score
        ]);
    }
}