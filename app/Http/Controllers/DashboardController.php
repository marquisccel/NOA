<?php

namespace App\Http\Controllers;

use App\Models\Siklus;
use App\Services\RingkasanDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RingkasanDashboard $ringkasan): View
    {
        $user = $request->user();

        if ($user->isPeternak()) {
            return view('dashboard.peternak', [
                'ringkasan' => $ringkasan->peternak($user),
                'aktivitas' => $ringkasan->aktivitas($user),
                'sapaan' => $this->sapaan(),
            ]);
        }

        if ($user->isAbk()) {
            $siklus = $user->aktif ? $user->siklusDitugaskan()->with('kandang')->first() : null;

            return view('dashboard.abk', [
                'siklus' => $siklus,
                'nomor' => $siklus ? (Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1) : null,
            ]);
        }

        return view('dashboard.tanpa-akses');
    }

    /** Sapaan menurut jam (zona waktu aplikasi). */
    private function sapaan(): string
    {
        $jam = (int) now()->format('G');

        return match (true) {
            $jam >= 4 && $jam < 11 => 'Selamat pagi',
            $jam >= 11 && $jam < 15 => 'Selamat siang',
            $jam >= 15 && $jam < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }
}
