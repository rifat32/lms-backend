<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Add authorization logic here, e.g., check if user has 'student' role
        return auth()->user()->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'bio'             => ['nullable', 'string', 'max:500'],
            'address_line_1'  => ['nullable', 'string', 'max:255'],

            // learning_preferences as an object
            'learning_preferences'                         => ['nullable', 'array'],
            'learning_preferences.preferred_learning_time' => ['nullable', 'string'],
            'learning_preferences.daily_goal'              => ['nullable', 'integer', 'min:1', 'max:24'],

            // interests can be an array of strings (key-value also works since Laravel treats objects as arrays)
            'interests'   => ['nullable', 'array'],
            'interests.*' => ['nullable', 'string', 'max:100'],

            // SOCIAL LINKS (object with optional URLs)
            'social_links'          => ['nullable', 'array'],
            'social_links.web_site' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.linkedin' => ['nullable', 'url'],
            'social_links.github'   => ['nullable', 'url'],
            'social_links.twitter'  => ['nullable', 'url'],
        ];
    }

    /**
     * Custom error messages (optional).
     *
     * @return array
     */
    public function messages()
    {
        return [
            'learning_preferences.preferred_learning_time.in' => 'Invalid learning time preference.',
            'learning_preferences.daily_goal.integer' => 'Daily goal must be a number of hours.',
        ];
    }
}
