<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;
use App\Models\PromotionCreditApprovalReport;

/**
 * App\Queriplex\PromotionCreditApprovalReportQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PromotionCreditApprovalReport make()
 */
class PromotionCreditApprovalReportQueriplex extends Queriplex
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
                    $query->where('status', $value);
                }
            },
            'status' => function ($query, $value) {
                if ($value == PromotionCreditApprovalReport::STATUS_PENDING) {
                    $query->where('status', PromotionCreditApprovalReport::STATUS_PENDING);
                } else {
                    $query->where('status', '!=', PromotionCreditApprovalReport::STATUS_PENDING);
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
        $searchColumns = ['users.name', 'users.ic'];

        $query->whereHas('user', function ($q) use ($searchColumns, $value) {
            $q->where(function ($q) use ($searchColumns, $value) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$value}%");
                }
            });
        });

        return null;
    }
}
