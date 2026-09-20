<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardReportService;
use App\Support\Reports\ReportAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardReportService $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowed = ReportAccess::allowedFor($user);

        // Figures are cached organisation-wide; each user only sees the
        // cards and charts their report permissions cover.
        $overview = $allowed === [] ? null : $this->dashboard->overview((bool) $request->boolean('fresh'));

        return view('admin.dashboard', [
            'overview' => $overview,
            'allowed' => $allowed,
            'can' => array_fill_keys($allowed, true),
            'systemStatus' => [
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug'),
                'queue_connection' => config('queue.default'),
            ],
        ]);
    }
}
