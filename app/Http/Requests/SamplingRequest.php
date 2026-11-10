<?php

namespace App\Http\Requests;

use App\Models\Siklus;
use App\Services\RingkasanSampling;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class SamplingRequest extends FormRequest
{
    /** Bobot per ekor di atas ini tidak masuk akal untuk broiler; kemungkinan salah ketik satuan. */
    private const ABW_MAKS_KG = 10;

    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    public function siklus(): Siklus
    {
        return $this->route('siklus') ?? $this->route('sampling')->siklus;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $masuk = $this->siklus()->tanggal_masuk->toDateString();

        return [
            'tanggal' => ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'],
            'jumlah_sampel' => ['required', 'integer', 'min:1', 'max:1000000'],
            'total_bobot_kg' => ['required', 'numeric', 'gt:0', 'max:9999999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $siklus = $this->siklus();
                $hariKe = $this->hariKe($siklus);

                if ($hariKe > 60) {
                    $validator->errors()->add('tanggal', 'Tanggal terlalu jauh dari tanggal masuk siklus.');

                    return;
                }

                $hidup = app(RingkasanSampling::class)->ayamHidup($siklus, $hariKe);
                if ((int) $this->input('jumlah_sampel') > $hidup) {
                    $validator->errors()->add('jumlah_sampel', 'Jumlah sampel melebihi ayam hidup pada hari itu ('.number_format($hidup, 0, ',', '.').' ekor).');

                    return;
                }

                if ((float) $this->input('total_bobot_kg') / (int) $this->input('jumlah_sampel') > self::ABW_MAKS_KG) {
                    $validator->errors()->add('total_bobot_kg', 'Bobot rata-rata per ekor lebih dari '.self::ABW_MAKS_KG.' kg. Periksa satuan: isi total bobot dalam kilogram.');
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
            'jumlah_sampel' => 'jumlah sampel',
            'total_bobot_kg' => 'total bobot',
        ];
    }
}
