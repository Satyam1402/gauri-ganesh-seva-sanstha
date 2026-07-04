<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $systemStatus = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'queue_connection' => config('queue.default'),
        ];

        return view('admin.dashboard', ['systemStatus' => $systemStatus]);
    }
}
