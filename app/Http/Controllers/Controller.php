<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Rows per page chosen in the table footer (?per_page=). */
    protected function perPage(Request $request, int $default = 20): int
    {
        $n = (int) $request->query('per_page', $default);
        return in_array($n, [10, 20, 25, 50, 100], true) ? $n : $default;
    }
}
