<?php

namespace App\Http\Requests;

use App\Models\Siklus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiklusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'tanggal_masuk' => ['required', 'date'],
            'populasi' => ['required', 'integer', 'min:1', 'max:1000000'],
            'strain_doc' => ['nullable', 'string', 'max:50'],
            'jenis_pakan' => ['nullable', 'string', 'max:50'],
            'nama_ppl' => ['nullable', 'string', 'max:255'],
            'nama_inti' => ['nullable', 'string', 'max:255'],
            'bobot_sak_kg' => ['required', 'numeric', 'gt:0', 'max:9999'],

            'kontrak.harga_doc' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'kontrak.harga_pakan_sak' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'kontrak.harga_ovk' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'kontrak.harga_kontrak_ayam' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'kontrak.bonus_fcr' => ['boolean'],
            'kontrak.bonus_deplesi' => ['boolean'],
            'kontrak.bonus_ip' => ['boolean'],
            'kontrak.bonus_harga_pasar' => ['boolean'],
        ];

        // Kandang hanya dipilih saat siklus dibuka; sesudahnya tetap.
        if ($this->isMethod('POST')) {
            $rules['kandang_id'] = [
                'required',
                Rule::exists('kandang', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    /** R2: satu kandang hanya boleh punya satu siklus berjalan. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->isMethod('POST') || $validator->errors()->has('kandang_id')) {
                    return;
                }

                $ada = Siklus::where('kandang_id', $this->integer('kandang_id'))
                    ->where('status', Siklus::STATUS_BERJALAN)
                    ->exists();

                if ($ada) {
                    $validator->errors()->add('kandang_id', 'Kandang ini masih punya siklus berjalan. Tandai selesai dulu sebelum membuka siklus baru.');
                }
            },
        ];
    }

    /** Checkbox yang tidak dicentang tidak terkirim: jadikan false. */
    protected function prepareForValidation(): void
    {
        $kontrak = (array) $this->input('kontrak', []);

        foreach (['bonus_fcr', 'bonus_deplesi', 'bonus_ip', 'bonus_harga_pasar'] as $bonus) {
            $kontrak[$bonus] = filter_var($kontrak[$bonus] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge(['kontrak' => $kontrak]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'kandang_id' => 'kandang',
            'tanggal_masuk' => 'tanggal masuk',
            'bobot_sak_kg' => 'bobot per sak',
            'kontrak.harga_doc' => 'harga DOC',
            'kontrak.harga_pakan_sak' => 'harga pakan per sak',
            'kontrak.harga_ovk' => 'harga OVK',
            'kontrak.harga_kontrak_ayam' => 'harga kontrak ayam',
        ];
    }
}
