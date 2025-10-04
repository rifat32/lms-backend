<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'required|string|max:255',
            'parent_question_category_id' => 'nullable|exists:question_categories,id'
        ];


        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['id'] = 'required|exists:question_categories,id';
        }
        return $rules;
    }
}
