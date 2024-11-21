<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\ExportHubQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportHub make()
 */
class ExportHubQueriplex extends Queriplex
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
            'activity' => function ($query, $value) {
                $query->whereActivity($value);
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
        $search_by = $this->getInput('search_by');

        $commonSearchable = [
            'id',
        ];

        if (in_array($search_by, $commonSearchable)) {
            $query->keywordSearch($search_by, $value);
        } else {
            switch ($search_by) {
                default:
                    break;
            }
        }

        return null;
    }
}
