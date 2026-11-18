<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Services\RingkasanDashboard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/** Log aktivitas: catatan terbaru recording, sampling, OVK, dan panen di semua kandang peternak. */
class AktivitasController extends Controller
{
    private const PER_HALAMAN = 25;

    public function __invoke(Request $request, RingkasanDashboard $ringkasan): View
    {
        $user = $request->user();
        $jenis = in_array($request->query('jenis'), RingkasanDashboard::JENIS, true) ? $request->query('jenis') : null;
        $kandangId = $request->integer('kandang') ?: null;

        $semua = $ringkasan->aktivitas($user, 200, $jenis, $kandangId);
        $halaman = max(1, $request->integer('halaman', 1));

        return view('aktivitas.index', [
            'aktivitas' => new LengthAwarePaginator(
                $semua->forPage($halaman, self::PER_HALAMAN)->values(),
                $semua->count(),
                self::PER_HALAMAN,
                $halaman,
                ['path' => $request->url(), 'pageName' => 'halaman', 'query' => $request->except('halaman')],
            ),
            'kandang' => Kandang::where('user_id', $user->id)->orderBy('nama_farm')->get(),
            'jenis' => RingkasanDashboard::JENIS,
            'filter' => ['jenis' => $jenis, 'kandang' => $kandangId],
        ]);
    }
}
