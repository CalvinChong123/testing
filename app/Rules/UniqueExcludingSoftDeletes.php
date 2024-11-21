<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UniqueExcludingSoftDeletes implements Rule
{
    protected $table;

    protected $column;

    protected $ignoreId;

    /**
     * Create a new rule instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  int|null  $ignoreId
     * @return void
     */
    public function __construct($table, $column = 'name', $ignoreId = null)
    {
        $this->table = $table;
        $this->column = $column;
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
        $query = DB::table($this->table)
            ->where($this->column, $value)
            ->whereNull('deleted_at');

        if ($this->ignoreId) {
            $query->where('id', '<>', $this->ignoreId);
        }

        return ! $query->exists();
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
