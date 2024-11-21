<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\CashFloatQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CashFloat make()
 */
class CashFloatQueriplex extends Queriplex
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
                if ($value == 'Completed') {
                    $query->where('cash_out', '!=', null);
                }
                if ($value == 'Incomplete') {
                    $query->where('cash_out', null);
                }
            },
            'search' => fn($query, $value) => $this->searchQuery($query, $value),
        ];
    }

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
        $searchColumns = [
            'cashInAdmin.name',
            'cashInAdmin.ic',
            'cashOutAdmin.name',
            'cashOutAdmin.ic'
        ];

        $query->where(function ($q) use ($searchColumns, $value) {
            foreach ($searchColumns as $column) {
                $relation = explode('.', $column)[0];
                $field = explode('.', $column)[1];

                $q->orWhereHas($relation, function ($q) use ($field, $value) {
                    $q->where($field, 'LIKE', "%{$value}%");
                });
            }
        });

        return null;
    }
}
