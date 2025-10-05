<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use Illuminate\Foundation\Http\FormRequest;

class SectionWithLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            "id" => "nullable|integer|exists:sections,id",
            'title' => 'required|string|max:255',
            'course_id' => ['required', 'numeric', new ValidCourse()],
            "order" => "required|integer",
            'sectionable' => 'array|required',
            'sectionable.*.id' => 'required|integer',
            'sectionable.*.type' => 'required|string|in:lesson,quiz',
            'sectionable.*.order' => 'nullable|integer',
        ];



        return $rules;
    }
}
