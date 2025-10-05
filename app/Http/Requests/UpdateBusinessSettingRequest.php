<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
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
        
        'stripe_enabled' => 'required|boolean',
        'STRIPE_KEY' => 'string|nullable|required_if:stripe_enabled,true',
        'STRIPE_SECRET' => 'string|nullable|required_if:stripe_enabled,true',
       

        ];

    }

}
