<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Parameter manajemen per rentang umur (F1.6) dan jadwal vaksin (F1.7). */
class AcuanSiklusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'parameter' => ['array', 'max:20'],
            'parameter.*.umur_awal' => ['required', 'integer', 'min:1', 'max:60'],
            'parameter.*.umur_akhir' => ['required', 'integer', 'min:1', 'max:60', 'gte:parameter.*.umur_awal'],
            'parameter.*.suhu_min' => ['nullable', 'numeric', 'between:0,50'],
            'parameter.*.suhu_maks' => ['nullable', 'numeric', 'between:0,50', 'gte:parameter.*.suhu_min'],
            'parameter.*.kepadatan_ekor_m2' => ['nullable', 'numeric', 'between:0,200'],
            'parameter.*.frekuensi_pakan' => ['nullable', 'integer', 'between:0,24'],
            'parameter.*.jumlah_tma' => ['nullable', 'integer', 'between:0,10000'],
            'parameter.*.jumlah_tra' => ['nullable', 'integer', 'between:0,10000'],

            'vaksin' => ['array', 'max:30'],
            'vaksin.*.hari_ke' => ['required', 'integer', 'min:1', 'max:60'],
            'vaksin.*.nama_vaksin' => ['required', 'string', 'max:255'],
            'vaksin.*.keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'parameter.*.umur_awal' => 'umur awal',
            'parameter.*.umur_akhir' => 'umur akhir',
            'parameter.*.suhu_min' => 'suhu minimum',
            'parameter.*.suhu_maks' => 'suhu maksimum',
            'parameter.*.kepadatan_ekor_m2' => 'kepadatan',
            'parameter.*.frekuensi_pakan' => 'frekuensi pakan',
            'parameter.*.jumlah_tma' => 'jumlah TMA',
            'parameter.*.jumlah_tra' => 'jumlah TRA',
            'vaksin.*.hari_ke' => 'hari ke',
            'vaksin.*.nama_vaksin' => 'nama vaksin',
        ];
    }
}
