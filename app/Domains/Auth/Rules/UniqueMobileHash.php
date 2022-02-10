<?php

namespace App\Domains\Auth\Rules;

use DB;
use Illuminate\Contracts\Validation\Rule;

class UniqueMobileHash implements Rule
{
    private $table;
    private $ignoreId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($table, $ignoreId = null)
    {
        $this->table = $table;

        $this->ignoreId = $ignoreId;
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
        $value = hash_sha($value);

        $query = DB::table($this->table)->where($attribute . '_hash', $value)->where('is_registered',1);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return ! (bool) $query->count();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute has already been taken';
    }
}
