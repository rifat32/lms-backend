<?php

namespace App\Http\Requests;

use App\Models\Section;
use App\Rules\ValidCourse;
use App\Rules\ValidSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            "id" => ["nullable", "integer", new ValidSection()],
            'title' => 'required|string|max:255',
            'course_id' => ['required', 'numeric', new ValidCourse()],
            "order" => "required|integer",
            'sectionable' => 'present|array',
            'sectionable.*.id' => 'required|integer',
            'sectionable.*.type' => ['required', 'string', Rule::in(array_values(Section::SECTIONABLE_TYPES))],
            'sectionable.*.order' => 'nullable|integer',
        ];



        return $rules;
    }

    public function messages()
    {
        return [
            'sectionable.*.type.required' => 'Section type is required.',
            'sectionable.*.type.in' => 'Section type must be one of: ' . implode(', ', array_values(Section::SECTIONABLE_TYPES)) . '.',
        ];
    }
}
