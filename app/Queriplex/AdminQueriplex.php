<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\UserQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User make()
 */
class AdminQueriplex extends Queriplex
{
    public $sortingKey = 'sort_by';

    /**
     * Get the filtering rules that apply to the model builder.
     *
     * @return array
     */
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
                if ($value !== 'All') {
                    if ($value == 'Disabled' || $value == 'Suspended') {
                        $query->whereHas('entityStatus', function ($q) use ($value) {

                            $q->where('status', '$value');
                        });
                    } else {
                        $query->whereDoesntHave('entityStatus');
                    }
                }
            },
            'roles' => function ($query, array $values) {
                $query->whereHas('roles', function ($q) use ($values) {
                    $q->whereIn('name', $values);
                });
            },
            'search' => (fn($query, $value) => $this->searchQuery($query, $value)),
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
        $searchColumns = ['name', 'ic', 'email'];

        $query->where(function ($q) use ($searchColumns, $value) {
            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$value}%");
            }
        });

        return null;
    }
}
