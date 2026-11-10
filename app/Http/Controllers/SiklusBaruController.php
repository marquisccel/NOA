<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Services\StandarHarianCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Wizard "Buka siklus baru" (C-01 sampai C-07). Isian disimpan sebagai draf di sesi sampai langkah 5;
 * siklus baru dibuat sekaligus (siklus, kontrak, skema bonus, tabel standar) saat peternak menekan "Buka Siklus".
 */
class SiklusBaruController extends Controller
{
    public const LANGKAH = [
        1 => 'Identitas & DOC',
        2 => 'Kontrak',
        3 => 'Skema bonus',
        4 => 'Standar harian',
        5 => 'Konfirmasi',
    ];

    private const BONUS = ['fcr', 'deplesi', 'ip', 'harga_pasar'];

    public function mulai(Kandang $kandang): RedirectResponse|View
    {
        Gate::authorize('update', $kandang);

        if ($terbuka = $this->siklusTerbuka($kandang)) {
            return $this->gagal($kandang, $terbuka);
        }

        return redirect()->route('siklus.baru.langkah', [$kandang, 1]);
    }

    public function tampil(Kandang $kandang, int $langkah): RedirectResponse|View
    {
        Gate::authorize('update', $kandang);

        if ($terbuka = $this->siklusTerbuka($kandang)) {
            return $this->gagal($kandang, $terbuka);
        }
        if (! isset(self::LANGKAH[$langkah])) {
            abort(404);
        }
        if (($balik = $this->pastikanLangkahSebelumnya($kandang, $langkah)) !== null) {
            return $balik;
        }

        $draf = $this->draf($kandang->id);

        return view('siklus.baru.langkah-'.$langkah, [
            'kandang' => $kandang,
            'langkah' => $langkah,
            'draf' => $draf,
            'konteks' => $this->konteks($kandang),
            'ringkasan' => $langkah === 5 ? $this->ringkasanKonfirmasi($draf) : null,
        ]);
    }

    public function simpan(Request $request, Kandang $kandang, int $langkah): RedirectResponse
    {
        Gate::authorize('update', $kandang);

        if ($terbuka = $this->siklusTerbuka($kandang)) {
            return redirect()->route('siklus.baru', $kandang);
        }
        if (! in_array($langkah, [1, 2, 3, 4], true)) {
            abort(404);
        }

        $draf = $this->draf($kandang->id);

        match ($langkah) {
            1 => $draf['identitas'] = $request->validate($this->aturanIdentitas($kandang), $this->pesanIdentitas($kandang), $this->atribut()),
            2 => $draf['kontrak'] = $request->validate($this->aturanKontrak(), [], $this->atribut()),
            3 => $draf['bonus'] = $this->validasiBonus($request),
            4 => $this->validasiStandar($draf),
        };

        $this->simpanDraf($kandang->id, $draf);

        return redirect()->route('siklus.baru.langkah', [$kandang, $langkah + 1]);
    }

    /** Langkah 4: unggah CSV dan tampilkan pratinjau; baris bermasalah dilaporkan, belum menyimpan apa pun ke siklus. */
    public function unggahStandar(Request $request, Kandang $kandang, StandarHarianCsv $csv): RedirectResponse
    {
        Gate::authorize('update', $kandang);

        $request->validate(['file' => ['required', 'file', 'max:512', 'mimes:csv,txt']], [
            'file.required' => 'Pilih file CSV terlebih dahulu.',
            'file.mimes' => 'File harus berformat CSV.',
        ], ['file' => 'file CSV']);

        $hasil = $csv->parse($request->file('file')->getRealPath());

        $draf = $this->draf($kandang->id);
        $draf['standar'] = [
            'nama' => $request->file('file')->getClientOriginalName(),
            'rows' => $hasil['rows'],
            'errors' => $hasil['errors'],
            'jumlah_baris' => $hasil['jumlah_baris'],
            'dilewati' => false,
        ];
        $this->simpanDraf($kandang->id, $draf);

        return redirect()->route('siklus.baru.langkah', [$kandang, 4]);
    }

    public function lewatiStandar(Kandang $kandang): RedirectResponse
    {
        Gate::authorize('update', $kandang);

        $draf = $this->draf($kandang->id);
        $draf['standar'] = ['nama' => null, 'rows' => [], 'errors' => [], 'jumlah_baris' => 0, 'dilewati' => true];
        $this->simpanDraf($kandang->id, $draf);

        return redirect()->route('siklus.baru.langkah', [$kandang, 5]);
    }

    public function buka(Kandang $kandang, StandarHarianCsv $csv): RedirectResponse|View
    {
        Gate::authorize('update', $kandang);

        if ($terbuka = $this->siklusTerbuka($kandang)) {
            return $this->gagal($kandang, $terbuka);
        }
        if (($balik = $this->pastikanLangkahSebelumnya($kandang, 5)) !== null) {
            return $balik;
        }

        $draf = $this->draf($kandang->id);

        $siklus = DB::transaction(function () use ($kandang, $draf, $csv) {
            $siklus = $kandang->siklus()->create($draf['identitas'] + ['status' => Siklus::STATUS_BERJALAN]);
            $siklus->kontrak()->create($draf['kontrak'] + $draf['bonus']);

            $rows = $draf['standar']['rows'] ?? [];
            if ($rows) {
                $siklus->standarHarian()->createMany($csv->lengkapi($rows, $siklus->populasi, (float) $siklus->bobot_sak_kg));
            }

            return $siklus;
        });

        session()->forget($this->kunci($kandang->id));

        return redirect()->route('siklus.dibuka', $siklus);
    }

    public function dibuka(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('siklus.baru.sukses', [
            'siklus' => $siklus->load('kandang'),
            'nomor' => Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1,
            'standar' => $siklus->standarHarian()->count(),
        ]);
    }

    // ---- aturan per langkah ----

    /** @return array<string, array<int, mixed>> */
    private function aturanIdentitas(Kandang $kandang): array
    {
        return [
            'tanggal_masuk' => ['required', 'date'],
            'populasi' => ['required', 'integer', 'min:1', 'max:'.$kandang->kapasitas],
            'strain_doc' => ['required', 'string', 'max:50'],
            'jenis_pakan' => ['required', 'string', 'max:50'],
            'nama_ppl' => ['required', 'string', 'max:255'],
            'nama_inti' => ['required', 'string', 'max:255'],
            'bobot_sak_kg' => ['required', 'numeric', 'gt:0', 'max:9999'],
        ];
    }

    /** @return array<string, string> */
    private function pesanIdentitas(Kandang $kandang): array
    {
        return [
            'tanggal_masuk.required' => 'Tanggal DOC masuk wajib diisi.',
            'populasi.max' => 'Melebihi kapasitas kandang ('.number_format($kandang->kapasitas, 0, ',', '.').' ekor).',
            'bobot_sak_kg.gt' => 'Bobot per sak harus lebih dari 0.',
            'bobot_sak_kg.required' => 'Bobot per sak wajib diisi.',
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function aturanKontrak(): array
    {
        return [
            'harga_doc' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'harga_pakan_sak' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'harga_ovk' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'harga_kontrak_ayam' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    /** @return array<string, int|float|bool|null> */
    private function validasiBonus(Request $request): array
    {
        $aturan = [];
        foreach (self::BONUS as $jenis) {
            $aturan["nilai_bonus_{$jenis}"] = ["required_if:bonus_{$jenis},1", 'nullable', 'numeric', 'min:0', 'max:9999999999999'];
        }
        $atribut = $this->atribut() + collect(self::BONUS)->mapWithKeys(fn ($j) => ["nilai_bonus_{$j}" => 'nilai bonus '.str_replace('_', ' ', $j)])->all();

        $request->merge(collect(self::BONUS)->mapWithKeys(fn ($j) => ["bonus_{$j}" => $request->boolean("bonus_{$j}") ? 1 : 0])->all());
        $data = $request->validate($aturan, [], $atribut);

        $hasil = [];
        foreach (self::BONUS as $jenis) {
            $aktif = $request->boolean("bonus_{$jenis}");
            $hasil["bonus_{$jenis}"] = $aktif;
            // Nilai hanya disimpan untuk bonus yang aktif.
            $hasil["nilai_bonus_{$jenis}"] = $aktif ? ($data["nilai_bonus_{$jenis}"] ?? null) : null;
        }

        return $hasil;
    }

    /** @param  array<string, mixed>  $draf */
    private function validasiStandar(array $draf): void
    {
        $standar = $draf['standar'] ?? null;

        if (! $standar || (! $standar['dilewati'] && ! $standar['rows'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Unggah file CSV standar, atau pilih “Lewati, isi nanti”.']);
        }
        if ($standar['errors']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Perbaiki baris yang bermasalah lalu unggah ulang, atau lewati.']);
        }
    }

    // ---- bantuan ----

    /** Langkah yang butuh data langkah sebelumnya: kembalikan peternak ke langkah pertama yang belum lengkap. */
    private function pastikanLangkahSebelumnya(Kandang $kandang, int $langkah): ?RedirectResponse
    {
        $draf = $this->draf($kandang->id);
        $syarat = [1 => 'identitas', 2 => 'kontrak', 3 => 'bonus', 4 => 'standar'];

        for ($i = 1; $i < $langkah; $i++) {
            if (empty($draf[$syarat[$i]] ?? null) && ! ($i === 3 && isset($draf['bonus']))) {
                return redirect()->route('siklus.baru.langkah', [$kandang, $i]);
            }
        }

        // Langkah 4 sah bila ada tabel valid atau dilewati.
        if ($langkah === 5) {
            $standar = $draf['standar'] ?? null;
            if (! $standar || $standar['errors'] || (! $standar['dilewati'] && ! $standar['rows'])) {
                return redirect()->route('siklus.baru.langkah', [$kandang, 4]);
            }
        }

        return null;
    }

    private function siklusTerbuka(Kandang $kandang): ?Siklus
    {
        return $kandang->siklus()->whereIn('status', Siklus::TERBUKA)->first();
    }

    private function gagal(Kandang $kandang, Siklus $terbuka): View
    {
        return view('siklus.baru.gagal', [
            'kandang' => $kandang,
            'terbuka' => $terbuka,
            'nomor' => Siklus::nomorMap([$kandang->id])[$terbuka->id] ?? 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function konteks(Kandang $kandang): array
    {
        $nomor = Siklus::nomorMap([$kandang->id]);
        $terakhir = $kandang->siklus()->orderByDesc('tanggal_masuk')->orderByDesc('id')->first();

        return [
            'berikutnya' => count($nomor) + 1,
            'terakhir' => $terakhir,
            'nomor_terakhir' => $terakhir ? ($nomor[$terakhir->id] ?? 1) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $draf
     * @return array<string, mixed>
     */
    private function ringkasanKonfirmasi(array $draf): array
    {
        return $draf;
    }

    /** @return array<string, string> */
    private function atribut(): array
    {
        return [
            'tanggal_masuk' => 'tanggal DOC masuk',
            'populasi' => 'populasi awal',
            'strain_doc' => 'strain DOC',
            'jenis_pakan' => 'jenis pakan',
            'nama_ppl' => 'nama PPL',
            'nama_inti' => 'nama inti',
            'bobot_sak_kg' => 'bobot per sak',
            'harga_doc' => 'harga DOC',
            'harga_pakan_sak' => 'harga pakan',
            'harga_ovk' => 'harga OVK',
            'harga_kontrak_ayam' => 'harga kontrak ayam',
        ];
    }

    private function kunci(int $kandangId): string
    {
        return "siklus_baru.{$kandangId}";
    }

    /** @return array<string, mixed> */
    private function draf(int $kandangId): array
    {
        return session($this->kunci($kandangId), []);
    }

    /** @param  array<string, mixed>  $draf */
    private function simpanDraf(int $kandangId, array $draf): void
    {
        session([$this->kunci($kandangId) => $draf]);
    }
}
