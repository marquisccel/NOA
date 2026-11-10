<?php

namespace App\Support;

use App\Models\User;

/**
 * Menu aplikasi per peran (sidebar, drawer, dan navigasi bawah di ponsel).
 *
 * Operasional (recording, sampling, OVK, panen) hidup di dalam siklus, bukan di sidebar global.
 * Hanya item yang halamannya sudah ada yang ditampilkan; tidak ada tautan buntu.
 */
class Navigasi
{
    /**
     * @return list<array{label: string, items: list<array{label: string, ikon: string, rute: string, aktif: list<string>}>}>
     */
    public static function grup(User $user): array
    {
        if ($user->isAbk()) {
            return [[
                'label' => 'Operasional',
                'items' => [
                    self::item('Siklus Ditugaskan', 'clipboard-list', 'dashboard', ['dashboard', 'recording.*']),
                ],
            ]];
        }

        if (! $user->isPeternak()) {
            return [];
        }

        return [
            [
                'label' => 'Utama',
                'items' => [
                    self::item('Dashboard', 'layout-dashboard', 'dashboard', ['dashboard']),
                    self::item('Kandang & Siklus', 'warehouse', 'kandang.index', [
                        'kandang.*', 'siklus.*', 'recording.*', 'sampling.*', 'ovk.*', 'panen.*', 'rhpp.*', 'laporan.*',
                    ]),
                ],
            ],
            [
                'label' => 'Manajemen',
                'items' => [
                    self::item('Pengguna & Akses', 'users', 'abk.index', ['abk.*']),
                    self::item('Pengaturan', 'settings', 'profile.edit', ['profile.*']),
                ],
            ],
        ];
    }

    /**
     * Tiga pintu utama di navigasi bawah ponsel; sisanya lewat "Lainnya" (drawer).
     *
     * @return list<array{label: string, ikon: string, rute: string, aktif: list<string>}>
     */
    public static function bawah(User $user): array
    {
        if (! $user->isPeternak()) {
            return [];
        }

        return [
            self::item('Dashboard', 'layout-dashboard', 'dashboard', ['dashboard']),
            self::item('Kandang', 'warehouse', 'kandang.index', ['kandang.*', 'siklus.*', 'recording.*', 'sampling.*', 'ovk.*', 'panen.*', 'rhpp.*', 'laporan.*']),
        ];
    }

    /** @return array{label: string, ikon: string, rute: string, aktif: list<string>} */
    private static function item(string $label, string $ikon, string $rute, array $aktif): array
    {
        return ['label' => $label, 'ikon' => $ikon, 'rute' => $rute, 'aktif' => $aktif];
    }

    /** True bila rute saat ini termasuk salah satu pola item. */
    public static function aktif(array $item): bool
    {
        return request()->routeIs(...$item['aktif']);
    }
}
