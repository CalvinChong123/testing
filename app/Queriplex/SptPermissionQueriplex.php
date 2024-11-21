<?php

namespace App\Queriplex;

use Kyrax324\Queriplex\Queriplex;

/**
 * App\Queriplex\SptPermissionQueriplex
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SptPermission make()
 */
class SptPermissionQueriplex extends Queriplex
{
    public $sortingKey = 'sort_by';

    public function filterRules()
    {
        return [
            'id' => 'id',
            'highest_classification_level' => (fn($query, $value) => $query->where('classification_level', '<', $value)),
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
            'title',
            'id',
        ];
        if (in_array($search_by, $commonSearchable)) {
            $query->keywordSearch($search_by, $value);
        }

        return null;
    }
}
