<?php

namespace App\Rules;

use App\Models\Business;
use Illuminate\Contracts\Validation\Rule;

class UniqueBusinessEmail implements Rule
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
        $business = Business::where('email', $value);

        if ($this->ignoredId) {
            $business->where('id', '!=', $this->ignoredId);
        }

        return !$business->exists();
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
