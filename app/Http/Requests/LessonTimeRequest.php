<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonTimeRequest extends FormRequest
{  public function authorize(): bool
    {
        // Keep true for now; add policies/enrollment checks if needed.
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => 'required|string|in:start,stop,heartbeat',
            // optional client timestamp if you want to pass client time
            'client_timestamp' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'event.in' => 'Event must be one of: start, stop, heartbeat',
        ];
    }
}
