<?php

namespace App\Http\Requests;

use App\Rules\ValidOption;
use App\Rules\ValidQuestion;
use Illuminate\Foundation\Http\FormRequest;

class OptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'question_id' => ['required', 'integer', new ValidQuestion()],
            'option_text' => 'required|string|max:255',
            'is_correct' => 'required|boolean',
        ];

        if ($this->isMethod('post') || $this->isMethod('put')) {
            $rules['id'] = ['required', 'integer', new ValidOption()];
        }

        return $rules;
    }
}
