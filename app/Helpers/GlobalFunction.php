<?php


if (!function_exists('retrieve_data')) {
    function retrieve_data($query, $orderBy, $tableName)
    {
        // Apply ordering
        if (request()->filled("order_by")) {
            $orderByColumn = request()->input("order_by");
        } else {
            $orderByColumn = $orderBy;
        }

        if (strpos($orderByColumn, '.') === false) {
            $orderByColumn = $tableName . "." . $orderByColumn;
        }

        $query = $query->orderBy(
            $orderByColumn,
            !empty(request()->order_by) && in_array(strtoupper(request()->order_by), ['ASC', 'DESC'])
                ? request()->order_by
                : 'DESC'
        );

        $perPage = request()->input('per_page', null);
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

        $meta = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'skip' => $skip,
            'total_pages' => $totalPages
        ];

        return [
            'data' => $data,
            'meta' => $meta
        ];
    }
}
