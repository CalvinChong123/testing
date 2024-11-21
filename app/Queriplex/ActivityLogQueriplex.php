<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\TransactionQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction make()
 */
class ActivityLogQueriplex extends Queriplex
{
    public $sortingKey = 'sort_by';

    public function filterRules()
    {
        return [
            'id' => 'id',
            'date_start' => function ($query, $value) {
                $query->whereDate('created_at', '>=', $value);
            },
            'date_end' => function ($query, $value) {
                $query->whereDate('created_at', '<=', $value);
            },
            'filter' => function ($query, $value) {
                $query->where('type', $value);
            },
            'advance' => function ($query, $value) {
                if ($value === 'false') {
                    $query->where('user_id', auth()->user()->id);
                }
            },
            'search' => fn($query, $value) => $this->searchQuery($query, $value),
        ];
    }

    /**
     * Get the sorting rules that apply to the model builder.
     *
     * @return array
     */
    public function sortRules()
    {
        $orderMode = $this->getInput('sort_desc') ? 'ASC' : 'DESC';

        return [
            'id' => fn($query) => $query->orderBy('id', $orderMode),
            'created_time' => fn($query) => $query->orderBy('created_at', $orderMode),
        ];
    }

    private function searchQuery($query, $value)
    {
        $searchColumns = ['module'];

        $query->where(function ($q) use ($searchColumns, $value) {
            $q->where('id', '=', $value);

            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$value}%");
            }

            $q->orWhereHas('user', function ($q) use ($value) {
                $q->where('name', 'LIKE', "%{$value}%");
            });
        });

        return null;
    }
}
