<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use App\Rules\ValidSection;
use Illuminate\Foundation\Http\FormRequest;

class SectionRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'course_id' => ['required', 'numeric', new ValidCourse()],
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            // For update, id is required
            $rules['id'] = ['required', 'integer', new ValidSection()];
        }

        return $rules;
    }
}
