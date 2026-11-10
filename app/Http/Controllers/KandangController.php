<?php

namespace App\Http\Controllers;

use App\Http\Requests\KandangRequest;
use App\Models\Kandang;
use App\Models\Siklus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KandangController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Kandang::class);

        $kandang = Kandang::where('user_id', auth()->id())
            ->with(['siklus' => fn ($q) => $q->where('status', Siklus::STATUS_BERJALAN)])
            ->orderBy('nama_farm')
            ->get();

        return view('kandang.index', compact('kandang'));
    }

    public function create(): View
    {
        Gate::authorize('create', Kandang::class);

        return view('kandang.form', ['kandang' => new Kandang]);
    }

    public function store(KandangRequest $request): RedirectResponse
    {
        $kandang = $request->user()->kandang()->create($request->validated());

        return redirect()->route('kandang.show', $kandang)->with('status', 'Kandang ditambahkan.');
    }

    public function show(Kandang $kandang): View
    {
        Gate::authorize('view', $kandang);

        $siklus = $kandang->siklus()->orderByDesc('tanggal_masuk')->get();

        return view('kandang.show', compact('kandang', 'siklus'));
    }

    public function edit(Kandang $kandang): View
    {
        Gate::authorize('update', $kandang);

        return view('kandang.form', compact('kandang'));
    }

    public function update(KandangRequest $request, Kandang $kandang): RedirectResponse
    {
        Gate::authorize('update', $kandang);

        $kandang->update($request->validated());

        return redirect()->route('kandang.show', $kandang)->with('status', 'Kandang diperbarui.');
    }

    public function destroy(Kandang $kandang): RedirectResponse
    {
        Gate::authorize('delete', $kandang);

        if ($kandang->siklus()->where('status', Siklus::STATUS_BERJALAN)->exists()) {
            return back()->withErrors(['kandang' => 'Kandang masih punya siklus berjalan, tidak bisa dihapus.']);
        }

        $kandang->delete();

        return redirect()->route('kandang.index')->with('status', 'Kandang dihapus.');
    }
}
