<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pengguna dan Akses: peternak membuat akun ABK, menugaskannya ke satu siklus, dan menonaktifkannya (F8.2).
 * Akun ABK tidak melihat kontrak, biaya, atau rekonsiliasi (R13).
 */
class AbkController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $abk = $request->user()->abk()
            ->with('siklusDitugaskan.kandang')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'ilike', "%{$q}%")->orWhere('email', 'ilike', "%{$q}%")))
            ->orderByDesc('aktif')->orderBy('name')
            ->get();

        $semua = $request->user()->abk();

        return view('abk.index', [
            'abk' => $abk,
            'q' => $q,
            'total' => (clone $semua)->count(),
            'aktif' => (clone $semua)->where('aktif', true)->count(),
            'nomor' => Siklus::nomorMap($abk->pluck('siklusDitugaskan.kandang_id')->filter()->unique()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('abk.form', ['abk' => new User] + $this->dataForm($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', Password::defaults()],
            'siklus_id' => $this->aturanSiklus($request->user()),
        ], $this->pesan(), $this->atribut());

        // role dan peternak_id ditentukan server, tidak pernah dari isian form.
        $abk = (new User)->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_ABK,
            'peternak_id' => $request->user()->id,
            'siklus_id' => $data['siklus_id'] ?? null,
            'aktif' => true,
            'email_verified_at' => now(),
        ]);
        $abk->save();

        event(new Registered($abk));

        $abk->load('siklusDitugaskan.kandang');

        return redirect()->route('abk.index')->with('status', "Akun ABK “{$abk->name}” berhasil dibuat".$this->tugasTeks($abk).'.');
    }

    public function edit(Request $request, User $abk): View
    {
        Gate::authorize('kelolaAbk', $abk);

        return view('abk.form', ['abk' => $abk->load('siklusDitugaskan.kandang')] + $this->dataForm($request->user()));
    }

    public function update(Request $request, User $abk): RedirectResponse
    {
        Gate::authorize('kelolaAbk', $abk);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($abk->id)],
            'siklus_id' => $this->aturanSiklus($request->user(), $abk),
        ], $this->pesan(), $this->atribut());

        // Select siklus yang dinonaktifkan tidak terkirim: berarti penugasan dicabut.
        $abk->forceFill(['name' => $data['name'], 'email' => $data['email'], 'siklus_id' => $data['siklus_id'] ?? null])->save();

        return redirect()->route('abk.index')->with('status', "Perubahan akun ABK “{$abk->name}” berhasil disimpan.");
    }

    /** Atur ulang kata sandi: dibuatkan sandi acak yang ditampilkan sekali agar peternak membagikannya langsung. */
    public function resetSandi(User $abk): RedirectResponse
    {
        Gate::authorize('kelolaAbk', $abk);

        // Memenuhi syarat kata sandi (huruf dan angka): satu huruf besar, lima karakter acak, empat angka.
        $baru = chr(random_int(65, 90)).Str::lower(Str::random(5)).random_int(1000, 9999);

        $abk->forceFill(['password' => Hash::make($baru), 'remember_token' => Str::random(60)])->save();

        return redirect()->route('abk.edit', $abk)->with('sandi_baru', $baru);
    }

    /** Nonaktifkan atau aktifkan akun. Nonaktif: tidak bisa masuk dan penugasannya dicabut; riwayat tetap. */
    public function status(Request $request, User $abk): RedirectResponse
    {
        Gate::authorize('kelolaAbk', $abk);

        $aktifkan = $request->boolean('aktif');

        $abk->forceFill($aktifkan ? ['aktif' => true] : ['aktif' => false, 'siklus_id' => null, 'remember_token' => Str::random(60)])->save();

        return redirect()->route('abk.index')->with('status', $aktifkan
            ? "Akun ABK “{$abk->name}” diaktifkan. Tugaskan ke siklus agar bisa mencatat."
            : "Akun ABK “{$abk->name}” dinonaktifkan. Riwayat recording yang sudah dicatat tetap tersimpan.");
    }

    /** Siklus yang boleh ditugaskan: berjalan, milik kandang peternak ini (atau yang sedang ditugaskan padanya). */
    private function aturanSiklus(User $peternak, ?User $abk = null): array
    {
        return ['nullable', 'integer', Rule::exists('siklus', 'id')->where(function ($q) use ($peternak, $abk) {
            $q->whereNull('deleted_at')
                ->whereIn('kandang_id', Kandang::where('user_id', $peternak->id)->select('id'))
                ->where(fn ($w) => $w->where('status', Siklus::STATUS_BERJALAN)->when($abk?->siklus_id, fn ($x) => $x->orWhere('id', $abk->siklus_id)));
        })];
    }

    /** Kandang beserta siklus berjalannya untuk pilihan penugasan. */
    private function dataForm(User $peternak): array
    {
        $kandang = Kandang::where('user_id', $peternak->id)->with('siklusBerjalan')->orderBy('nama_farm')->get()
            ->filter(fn ($k) => $k->siklusBerjalan)->values();
        $nomor = Siklus::nomorMap($kandang->pluck('id'));

        return [
            'pilihan' => $kandang->map(fn ($k) => [
                'id' => $k->id,
                'nama' => $k->nama_farm,
                'siklus_id' => $k->siklusBerjalan->id,
                'siklus' => 'Siklus '.($nomor[$k->siklusBerjalan->id] ?? 1).' · '.$k->siklusBerjalan->labelStatus(),
            ])->all(),
        ];
    }

    private function tugasTeks(User $abk): string
    {
        $s = $abk->siklusDitugaskan;

        return $s ? ' dan ditugaskan ke '.$s->kandang->nama_farm.' · Siklus '.(Siklus::nomorMap([$s->kandang_id])[$s->id] ?? 1) : '';
    }

    /** @return array<string, string> */
    private function pesan(): array
    {
        return [
            'name.required' => 'Nama ABK wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan email yang valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.required' => 'Kata sandi awal wajib diisi.',
            'siklus_id.exists' => 'Pilih siklus berjalan milik kandang Anda.',
        ];
    }

    /** @return array<string, string> */
    private function atribut(): array
    {
        return ['name' => 'nama ABK', 'password' => 'kata sandi awal', 'siklus_id' => 'siklus'];
    }
}
