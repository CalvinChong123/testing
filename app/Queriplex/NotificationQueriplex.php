<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\NotificationQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification make()
 */
class NotificationQueriplex extends Queriplex
{
    public $sortingKey = 'sort_by';

    public function filterRules()
    {
        return [
            'id' => 'id',
            'user_id' => function ($query, $value) {
                $query->where('user_id', $value);
            },
            'date_start' => function ($query, $value) {
                $query->whereDate('created_at', '>=', $value);
            },
            'date_end' => function ($query, $value) {
                $query->whereDate('created_at', '<=', $value);
            },
            'filter' => function ($query, $value) {
                if ($value !== 'All') {
                    $query->where('notification_type', $value);
                }
            },
            'type' => function ($query, $value) {
                if ($value == 'notification') {
                    $query->where('notification_type', '!=', 'system');
                }
                if ($value == 'systemAlert') {
                    $query->where('notification_type', 'system');
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
        $searchColumns = ['notification_type'];

        $query->where(function ($q) use ($searchColumns, $value) {
            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$value}%");
            }
        });

        return null;
    }
}
