<?php

namespace App\Swagger\Schemas;


/**
 * @OA\Schema(
 *     schema="QuestionResponse",
 *     type="object",
 *     title="Question API Response",
 *     description="Standard response for question-related endpoints",
 *     required={"success", "message", "question"},
 *     @OA\Property(
 *         property="success", type="boolean", example=true, 
 *         description="Indicates if the operation was successful"
 *     ),
 *     @OA\Property(
 *         property="message", type="string", example="Question created successfully", 
 *         description="Descriptive message about the result"
 *     ),
 *     @OA\Property(
 *         property="question",
 *         type="object",
 *         description="The question data",
 *         required={"id", "title", "body", "created_at", "updated_at"},
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="title", type="string", example="What is Laravel?"),
 *         @OA\Property(property="body", type="string", example="Laravel is a PHP framework..."),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-25T14:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-25T15:00:00Z")
 *     )
 * )
 */


class QuestionSchema {}
