<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    // GET /api/quizzes/{id}
    public function show($id)
    {
        $quiz = Quiz::with(['questions.options'])->findOrFail($id);

        return response()->json([
            'id' => $quiz->id,
            'title' => $quiz->title,
            'questions' => $quiz->questions->map(function($question) {
                return [
                    'question_id' => $question->id,
                    'text' => $question->question_text,
                    'type' => $question->question_type,
                    'options' => $question->options->map(function($option) {
                        return [
                            'option_id' => $option->id,
                            'text' => $option->option_text
                        ];
                    }),
                ];
            }),
        ]);
    }
}