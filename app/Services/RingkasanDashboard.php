<?php

namespace App\Services;

use App\Models\Kandang;
use App\Support\Format;
use App\Models\PemberianOvk;
use App\Models\Panen;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Angka ringkas dan aktivitas terbaru untuk dashboard peternak.
 * Semua dihitung dari data siklus; tidak ada angka yang disimpan terpisah.
 */
class RingkasanDashboard
{
    /** Jenis aktivitas yang dicatat, dipakai sebagai pilihan filter. */
    public const JENIS = ['Recording harian', 'Sampling bobot', 'Pemberian OVK', 'Panen'];

    /** Daftar id kandang dan siklus terbuka per pengguna, dimuat sekali per instance (satu request). */
    private array $kandangIdsMemo = [];

    private array $terbukaMemo = [];

    /** @return Collection<int, int> */
    private function kandangIds(User $user): Collection
    {
        return $this->kandangIdsMemo[$user->id] ??= Kandang::where('user_id', $user->id)->pluck('id');
    }

    /** Siklus yang belum ditutup (berjalan sampai rekonsiliasi), beserta kandangnya, urut tanggal masuk. */
    private function terbuka(User $user): Collection
    {
        return $this->terbukaMemo[$user->id] ??= Siklus::whereIn('kandang_id', $this->kandangIds($user))
            ->whereIn('status', Siklus::TERBUKA)
            ->with('kandang')
            ->orderBy('tanggal_masuk')
            ->get();
    }

    /**
     * @return array{total_kandang: int, kandang_aktif: int, siklus_berjalan: int, populasi_aktif: int, siklus: Collection<int, array<string, mixed>>}
     */
    public function peternak(User $user): array
    {
        $kandangIds = $this->kandangIds($user);
        $berjalan = $this->terbuka($user);

        $nomor = Siklus::nomorMap($kandangIds);
        $deplesi = RecordingHarian::whereIn('siklus_id', $berjalan->pluck('id'))
            ->selectRaw('siklus_id, sum(deplesi_harian) as total')
            ->groupBy('siklus_id')
            ->pluck('total', 'siklus_id');

        $kartu = $berjalan->map(function (Siklus $s) use ($nomor, $deplesi) {
            return [
                'siklus' => $s,
                'nomor' => $nomor[$s->id] ?? 1,
                'umur' => $s->umurHari(),
                'populasi_kini' => $s->populasi - (int) ($deplesi[$s->id] ?? 0),
            ];
        });

        return [
            'total_kandang' => $kandangIds->count(),
            'kandang_aktif' => $berjalan->pluck('kandang_id')->unique()->count(),
            // Kartu "Siklus Berjalan" dan populasi hanya menghitung yang masih dalam pemeliharaan;
            // daftar siklus aktif juga memuat yang menunggu RHPP.
            'siklus_berjalan' => $kartu->filter(fn ($k) => $k['siklus']->status === Siklus::STATUS_BERJALAN)->count(),
            'populasi_aktif' => (int) $kartu->filter(fn ($k) => $k['siklus']->status === Siklus::STATUS_BERJALAN)->sum('populasi_kini'),
            'siklus' => $kartu,
        ];
    }

    /**
     * Lima catatan terakhir lintas recording, sampling, OVK, dan panen.
     *
     * @return Collection<int, array{jenis: string, ikon: string, keterangan: string, tanggal: Carbon}>
     */
    public function aktivitas(User $user, int $batas = 5, ?string $jenisFilter = null, ?int $kandangFilter = null): Collection
    {
        $kandangIds = Kandang::where('user_id', $user->id)->when($kandangFilter, fn ($q) => $q->whereKey($kandangFilter))->pluck('id');
        // Siklus dan kandangnya dimuat sekali lalu dipasang ke tiap catatan (sebelumnya dimuat ulang per jenis catatan).
        $siklusMap = Siklus::whereIn('kandang_id', $kandangIds)->with('kandang')->get()->keyBy('id');
        $siklusIds = $siklusMap->keys();
        $nomor = Siklus::nomorMap($this->kandangIds($user));

        $sumber = [
            ['Recording harian', 'clipboard-list', RecordingHarian::class, fn ($r) => 'Hari ke-'.$r->hari_ke, 'recording.index', fn ($r) => 'Pakan '.Format::angka($r->pakan_pakai_sak).' sak · mati '.$r->mati.' · afkir '.$r->afkir],
            ['Sampling bobot', 'scale', SamplingBobot::class, fn ($r) => 'Hari ke-'.$r->hari_ke, 'sampling.index', fn ($r) => $r->jumlah_sampel.' ekor ditimbang · '.Format::angka((float) $r->total_bobot_kg).' kg'],
            ['Pemberian OVK', 'syringe', PemberianOvk::class, fn ($r) => 'Hari ke-'.($r->siklus->umurHari($r->tanggal)), 'ovk.index', fn ($r) => ucfirst($r->jenis).' · '.$r->nama_produk],
            ['Panen', 'truck', Panen::class, fn ($r) => 'umur '.$r->umur_hari.' hari', 'panen.index', fn ($r) => Format::angka($r->jumlah_ekor, 0).' ekor · '.Format::angka((float) $r->tonase_kg, 0).' kg'],
        ];

        $semua = collect();
        foreach ($sumber as [$jenis, $ikon, $model, $detail, $rute, $ringkas]) {
            if ($jenisFilter && $jenisFilter !== $jenis) {
                continue;
            }

            $model::whereIn('siklus_id', $siklusIds)
                ->orderByDesc('tanggal')->orderByDesc('id')
                ->limit($batas)
                ->get()
                ->each(fn ($r) => $r->setRelation('siklus', $siklusMap[$r->siklus_id]))
                ->each(fn ($r) => $semua->push([
                    'jenis' => $jenis,
                    'ikon' => $ikon,
                    'keterangan' => $r->siklus->kandang->nama_farm.' · Periode '.($nomor[$r->siklus_id] ?? 1).' · '.$detail($r),
                    'ringkas' => $ringkas($r),
                    'url' => route($rute, $r->siklus_id),
                    'tanggal' => Carbon::parse($r->tanggal),
                    'urut' => $r->id,
                ]));
        }

        return $semua->sortByDesc(fn ($a) => [$a['tanggal']->timestamp, $a['urut']])->take($batas)->values();
    }

    /**
     * Hal yang perlu ditindaklanjuti di semua siklus yang masih terbuka (recording terlewat, stok tipis, paraf, ...),
     * diambil dari ringkasan tiap siklus dan diurutkan: yang paling mendesak dulu.
     *
     * @return Collection<int, array{siklus: string, judul: string, teks: string, tautan: string, label: string, tingkat: string}>
     */
    public function perhatian(User $user, int $batas = 6): Collection
    {
        $nomor = Siklus::nomorMap($this->kandangIds($user));
        $ringkasan = app(RingkasanSiklus::class);

        $semua = collect();
        $this->terbuka($user)->filter(fn (Siklus $s) => $s->status === Siklus::STATUS_BERJALAN)
            ->each(function (Siklus $s) use ($ringkasan, $nomor, $semua) {
                foreach ($ringkasan->hitung($s)['perhatian'] as $h) {
                    $mendesak = str_contains($h['judul'], 'belum diisi') || str_starts_with($h['judul'], 'Stok pakan');
                    $semua->push($h + ['siklus' => $s->kandang->nama_farm.' · Periode '.($nomor[$s->id] ?? 1), 'tingkat' => $mendesak ? 'penting' : 'info']);
                }
            });

        return $semua->sortBy(fn ($h) => $h['tingkat'] === 'penting' ? 0 : 1)->values()->take($batas);
    }

    /**
     * Daftar langkah awal dengan tanda selesai, untuk pengguna baru: dari mendaftarkan kandang sampai mencocokkan RHPP.
     *
     * @return list<array{judul: string, teks: string, selesai: bool, tautan: string, label: string}>
     */
    public function langkahAwal(User $user): array
    {
        $kandangIds = $this->kandangIds($user);
        $semuaSiklus = Siklus::whereIn('kandang_id', $kandangIds)->orderBy('id')->get(['id']);
        $siklusIds = $semuaSiklus->pluck('id');
        $pertama = $semuaSiklus->first();

        return [
            ['judul' => 'Daftarkan kandang', 'teks' => 'Nama, alamat, dan kapasitas kandang.', 'selesai' => $kandangIds->isNotEmpty(), 'tautan' => route('kandang.create'), 'label' => 'Tambah kandang'],
            ['judul' => 'Buka periode pertama', 'teks' => 'Tanggal DOC masuk, jumlah ayam, dan harga kontrak.', 'selesai' => $siklusIds->isNotEmpty(), 'tautan' => route('kandang.index'), 'label' => 'Buka periode'],
            ['judul' => 'Isi catatan harian', 'teks' => 'Pakan, ayam mati, dan ayam afkir. Kurang dari satu menit.', 'selesai' => RecordingHarian::whereIn('siklus_id', $siklusIds)->exists(), 'tautan' => $pertama ? route('recording.create', $pertama) : route('kandang.index'), 'label' => 'Isi catatan'],
            ['judul' => 'Timbang sampel atau catat obat', 'teks' => 'Sekitar seminggu sekali; obat dan vaksin tiap diberikan.', 'selesai' => SamplingBobot::whereIn('siklus_id', $siklusIds)->exists() || PemberianOvk::whereIn('siklus_id', $siklusIds)->exists(), 'tautan' => $pertama ? route('sampling.create', $pertama) : route('kandang.index'), 'label' => 'Timbang sampel'],
            ['judul' => 'Catat panen', 'teks' => 'Tiap tahap panen dengan nomor BTPA.', 'selesai' => Panen::whereIn('siklus_id', $siklusIds)->exists(), 'tautan' => $pertama ? route('panen.create', $pertama) : route('kandang.index'), 'label' => 'Catat panen'],
            ['judul' => 'Cocokkan dengan RHPP', 'teks' => 'Masukkan RHPP dari inti dan lihat selisihnya.', 'selesai' => \App\Models\Rhpp::whereIn('siklus_id', $siklusIds)->exists(), 'tautan' => $pertama ? route('rhpp.show', $pertama) : route('kandang.index'), 'label' => 'Ke RHPP'],
        ];
    }
}
