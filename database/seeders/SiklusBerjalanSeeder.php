<?php

namespace Database\Seeders;

use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Satu siklus yang sedang berjalan (umur 17 hari) di kandang kedua, untuk melihat dashboard,
 * recording, dan akun ABK dengan data hidup. Jalankan setelah SiklusContohSeeder.
 */
class SiklusBerjalanSeeder extends Seeder
{
    public function run(): void
    {
        $peternak = User::where('email', 'peternak@noa.test')->firstOrFail();
        $abk = User::where('email', 'abk@noa.test')->first();

        $kandang = Kandang::create([
            'user_id' => $peternak->id,
            'nama_farm' => 'Kandang Contoh 2',
            'alamat' => 'Desa Pujon Kidul, Kec. Pujon, Kab. Malang',
            'kapasitas' => 6000,
            'tipe' => Kandang::TIPE_OPEN_HOUSE,
        ]);

        $masuk = now()->startOfDay()->subDays(16);
        $siklus = Siklus::create([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => $masuk,
            'populasi' => 3000,
            'strain_doc' => 'Cobb 500',
            'jenis_pakan' => 'BR-1',
            'nama_ppl' => 'PPL Contoh',
            'nama_inti' => 'PT Inti Contoh 2',
            'bobot_sak_kg' => 50,
            'status' => Siklus::STATUS_BERJALAN,
        ]);

        for ($hari = 1; $hari <= 17; $hari++) {
            RecordingHarian::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->copy()->addDays($hari - 1),
                'hari_ke' => $hari,
                'pakan_masuk_sak' => $hari % 6 === 1 ? 40 : 0,
                'pakan_pakai_sak' => round(1 + $hari * 0.45, 2),
                'mati' => $hari % 3,
                'afkir' => $hari % 8 === 0 ? 1 : 0,
                'sebab_afkir' => $hari % 8 === 0 ? 'sakit' : null,
                'diparaf_ppl' => $hari <= 7,
                'tanggal_paraf' => $hari <= 7 ? $masuk->copy()->addDays($hari) : null,
            ]);
        }

        foreach ([7, 14] as $hari) {
            SamplingBobot::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->copy()->addDays($hari - 1),
                'hari_ke' => $hari,
                'jumlah_sampel' => 150,
                'total_bobot_kg' => round(150 * (0.04 + $hari * 0.032), 3),
            ]);
        }

        PemberianOvk::create(['siklus_id' => $siklus->id, 'tanggal' => $masuk->copy()->addDays(3), 'jenis' => 'vaksin', 'nama_produk' => 'ND-IB', 'dosis' => '1 dosis/ekor', 'biaya' => 150000]);

        $abk?->forceFill(['siklus_id' => $siklus->id])->save();
    }
}
