<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RhppRequest extends FormRequest
{
    /** Kolom wajib beserta labelnya, sesuai lembar RHPP inti (tanda * di desain). */
    public const WAJIB = [
        'nomor_rhpp' => 'Nomor RHPP',
        'tanggal_terbit' => 'Tanggal terbit',
        'populasi_doc' => 'Populasi DOC',
        'total_ekor' => 'Ayam dipanen',
        'deplesi_ekor' => 'Deplesi',
        'total_tonase' => 'Tonase',
        'pakan_terpakai_kg' => 'Pakan terpakai',
        'fcr' => 'FCR',
        'ip' => 'IP',
        'abw' => 'ABW',
        'umur_panen' => 'Umur panen',
        'biaya_doc' => 'Biaya DOC',
        'biaya_pakan' => 'Biaya pakan',
        'biaya_ovk' => 'Biaya OVK',
        'pendapatan' => 'Pendapatan',
        'hasil_bersih' => 'Hasil bersih',
    ];

    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $masuk = $this->route('siklus')->tanggal_masuk->toDateString();

        return [
            'nomor_rhpp' => ['required', 'string', 'max:100'],
            'tanggal_terbit' => ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'],
            'lampiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'populasi_doc' => ['required', 'integer', 'min:0', 'max:1000000'],
            'total_ekor' => ['required', 'integer', 'min:0', 'max:1000000'],
            'deplesi_ekor' => ['required', 'integer', 'min:0', 'max:1000000'],
            'total_tonase' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'pakan_terpakai_kg' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'pakan_retur_sak' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'fcr' => ['required', 'numeric', 'min:0', 'max:99'],
            'ip' => ['required', 'numeric', 'min:0', 'max:99999'],
            'abw' => ['required', 'numeric', 'min:0', 'max:99'],
            'umur_panen' => ['required', 'numeric', 'min:0', 'max:999'],
            'biaya_doc' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'biaya_pakan' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'biaya_ovk' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'pendapatan' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'bonus' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'hasil_bersih' => ['required', 'numeric', 'between:-9999999999999,9999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Wajib diisi.',
            'lampiran.mimes' => 'Lampiran harus berupa JPG, PNG, WebP, atau PDF.',
            'lampiran.max' => 'Lampiran maksimal 5 MB.',
        ];
    }
}
