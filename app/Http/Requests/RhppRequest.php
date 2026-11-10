<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RhppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $masuk = $this->route('siklus')->tanggal_masuk->toDateString();

        return [
            'tanggal_terbit' => ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'],
            'total_pakan_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'pakan_retur_sak' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'total_ekor' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'total_tonase' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'abw' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'umur_panen' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'deplesi_persen' => ['nullable', 'numeric', 'between:0,100'],
            'fcr' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'ip' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'pendapatan' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tanggal_terbit' => 'tanggal terbit RHPP',
            'total_pakan_kg' => 'total pakan (tagihan)',
            'pakan_retur_sak' => 'pakan retur',
            'total_ekor' => 'total ekor',
            'total_tonase' => 'total tonase',
            'abw' => 'ABW',
            'umur_panen' => 'umur panen',
            'deplesi_persen' => 'deplesi',
            'fcr' => 'FCR',
            'ip' => 'IP',
        ];
    }
}
