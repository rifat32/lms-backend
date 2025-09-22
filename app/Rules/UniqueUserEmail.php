<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueUserEmail implements Rule
{
    protected $ignoredId;

    public function __construct($ignoredId = null)
    {
        $this->ignoredId = $ignoredId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $query = User::where('email', $value);

        if ($this->ignoredId) {
            $query->where('id', '!=', $this->ignoredId);
        }

        return !$query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}
