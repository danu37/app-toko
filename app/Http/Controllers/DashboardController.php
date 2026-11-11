<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransaksiResource;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporanService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $data = $this->laporanService->getDashboardSummary($user);

        $props = [
            'summary' => $data['summary'],
            'recentTransactions' => TransaksiResource::collection($data['recentTransactions']),
        ];

        if ($user->isAdmin()) {
            $props['salesChart'] = $data['salesChart'] ?? ['daily' => [], 'monthly' => []];
            $props['topProducts'] = $data['topProducts'] ?? [];
            $props['stockSummary'] = $data['stockSummary'] ?? null;
        }

        return Inertia::render('Dashboard/Index', $props);
    }
}
