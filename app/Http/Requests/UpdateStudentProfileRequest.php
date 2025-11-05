<?php

namespace App\Http\Requests;

use App\Rules\ValidUser;
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

    public function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id') ?? $this->input('id')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules(): array
    {
        $mimes = 'jpg,jpeg,png,webp,avif,gif';
        $maxKB = 5120; // 5MB

        return [
            // StudentProfile fields
            'id' => ['required', 'integer', new ValidUser()],
            'bio'                         => ['nullable', 'string', 'max:500'],
            'address_line_1'              => ['nullable', 'string', 'max:255'],

            'learning_preferences'                         => ['nullable', 'array'],
            'learning_preferences.preferred_learning_time' => ['nullable', 'string'],
            'learning_preferences.daily_goal'              => ['nullable', 'integer', 'min:1', 'max:24'],

            'interests'   => ['nullable', 'array'],
            'interests.*' => ['nullable', 'string', 'max:100'],

            // Social links
            'social_links'          => ['nullable', 'array'],
            'social_links.web_site' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.linkedin' => ['nullable', 'url'],
            'social_links.github'   => ['nullable', 'url'],
            'social_links.twitter'  => ['nullable', 'url'],

            // User updatable fields
            'user'              => ['nullable', 'array'],
            'user.title'        => ['nullable', 'string', 'max:50'],
            'user.first_name'   => ['nullable', 'string', 'max:100'],
            'user.last_name'    => ['nullable', 'string', 'max:100'],
            'user.phone'        => ['nullable', 'string', 'max:50'],

            // profile_photo can be a file OR omitted
            'user.profile_photo' => ['nullable', 'file', "mimes:$mimes", "max:$maxKB"],
        ];
    }

    public function messages(): array
    {
        return [
            'learning_preferences.preferred_learning_time.in' => 'Preferred learning time must be one of: morning, afternoon, evening, night.',
            'user.profile_photo.file'  => 'Profile photo must be an image file.',
            'user.profile_photo.mimes' => 'Profile photo must be one of: jpg, jpeg, png, webp, avif, gif.',
            'user.profile_photo.max'   => 'Profile photo may not be greater than 5MB.',
        ];
    }
}
