<?php

namespace App\Http\Requests;

use App\Rules\ValidCourseCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseCategoryRequest extends FormRequest
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
        return [
            // 'id' => [
            //     'required',
            //     'integer',
            //     new ValidCourseCategory(),
            // ],
            'name' => 'required|string|max:255',
        ];
    }
}
