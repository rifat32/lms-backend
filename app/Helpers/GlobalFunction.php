<?php


if (!function_exists('retrieve_data')) {
    function retrieve_data($query, $orderBy = 'created_at', $tableName)
    {
        // Get order column and sort order
        $orderByColumn = request()->input('order_by', $orderBy);
        $sortOrder = strtoupper(request()->input('sort_order', 'DESC'));

        // Ensure sort_order is valid
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        // Add table prefix if not included
        if (strpos($orderByColumn, '.') === false) {
            $orderByColumn = $tableName . '.' . $orderByColumn;
        }

        // Apply ordering
        $query = $query->orderBy($orderByColumn, $sortOrder);

        // Pagination setup
        $perPage = request()->input('per_page');
        $currentPage = request()->input('page', 1);
        $skip = 0;
        $total = 0;
        $totalPages = 1;

        if ($perPage) {
            $paginated = $query->paginate($perPage, ['*'], 'page', $currentPage);

            $data = $paginated->items();
            $skip = ($currentPage - 1) * $perPage;
            $total = $paginated->total();
            $perPage = $paginated->perPage();
            $currentPage = $paginated->currentPage();
            $totalPages = $paginated->lastPage();
        } else {
            $data = $query->get();
            $total = $data->count();
        }

        // Meta info
        $meta = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'skip' => $skip,
            'total_pages' => $totalPages,
        ];

        // Return data with meta
        return [
            'data' => $data,
            'meta' => $meta,
        ];
    }
}
