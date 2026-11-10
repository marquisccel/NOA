<?php

namespace App\Http\Requests;

use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Services\RingkasanRecording;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class RecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    public function siklus(): Siklus
    {
        return $this->route('siklus') ?? $this->route('recording')->siklus;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'pakan_masuk_sak' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'pakan_pakai_sak' => ['required', 'numeric', 'min:0', 'max:99999'],
            'pakan_sisa_sak' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'mati' => ['required', 'integer', 'min:0', 'max:1000000'],
            'afkir' => ['required', 'integer', 'min:0', 'max:1000000'],
            'sebab_afkir' => ['nullable', Rule::in(RecordingHarian::SEBAB_AFKIR), Rule::requiredIf(fn () => (int) $this->input('afkir') > 0)],
            'bobot_aktual_gram' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'suhu_pagi' => ['nullable', 'numeric', 'between:0,50'],
            'suhu_sore' => ['nullable', 'numeric', 'between:0,50'],
            'ovk_pagi' => ['nullable', 'string', 'max:500'],
            'ovk_sore' => ['nullable', 'string', 'max:500'],
        ];

        // Tanggal hanya ditentukan saat entri dibuat; baris yang disunting tetap di hari umurnya.
        if ($this->isMethod('POST')) {
            $masuk = $this->siklus()->tanggal_masuk->toDateString();
            $rules['tanggal'] = ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'];
        }

        return $rules;
    }

    /** Empat bidang wajib hanya pakan terpakai, mati, afkir, tanggal: mati dan afkir kosong berarti 0. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Sebab afkir hanya bermakna bila ada ayam afkir.
            'sebab_afkir' => (int) $this->input('afkir') > 0 ? $this->input('sebab_afkir') : null,
            'mati' => $this->input('mati') === null || $this->input('mati') === '' ? 0 : $this->input('mati'),
            'afkir' => $this->input('afkir') === null || $this->input('afkir') === '' ? 0 : $this->input('afkir'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $siklus = $this->siklus();
                $recording = $this->route('recording');
                $hariKe = $recording?->hari_ke ?? $this->hariKe($siklus);

                // F2.13: satu entri per hari umur.
                if (! $recording && $siklus->recordings()->where('hari_ke', $hariKe)->exists()) {
                    $validator->errors()->add('tanggal', "Hari ke-{$hariKe} sudah punya entri. Ubah entri yang ada.");

                    return;
                }

                if ($hariKe > 60) {
                    $validator->errors()->add('tanggal', 'Tanggal terlalu jauh dari tanggal masuk siklus.');

                    return;
                }

                // Deplesi kumulatif tidak boleh melebihi populasi awal.
                $lain = app(RingkasanRecording::class)->deplesiLain($siklus, $recording?->id);
                if ($lain + (int) $this->input('mati') + (int) $this->input('afkir') > $siklus->populasi) {
                    $validator->errors()->add('mati', 'Total ayam mati dan afkir melebihi populasi awal siklus.');
                }
            },
        ];
    }

    public function hariKe(Siklus $siklus): int
    {
        $tanggal = Carbon::parse($this->input('tanggal'))->startOfDay();

        return (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays($tanggal) + 1;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'pakan_masuk_sak' => 'pakan masuk',
            'pakan_pakai_sak' => 'pakan terpakai',
            'pakan_sisa_sak' => 'pakan sisa',
            'sebab_afkir' => 'sebab afkir',
            'bobot_aktual_gram' => 'bobot aktual',
            'suhu_pagi' => 'suhu pagi',
            'suhu_sore' => 'suhu sore',
        ];
    }
}
