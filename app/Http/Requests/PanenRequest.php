<?php

namespace App\Http\Requests;

use App\Models\Siklus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PanenRequest extends FormRequest
{
    /** Bobot rata-rata per ekor di atas ini tidak masuk akal untuk broiler; kemungkinan salah satuan. */
    private const ABW_MAKS_KG = 10;

    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    public function siklus(): Siklus
    {
        return $this->route('siklus') ?? $this->route('panen')->siklus;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $masuk = $this->siklus()->tanggal_masuk->toDateString();

        return [
            'tanggal' => ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'],
            'jumlah_ekor' => ['required', 'integer', 'min:1', 'max:1000000'],
            'tonase_kg' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'harga_per_kg' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'nomor_btpa' => ['nullable', 'string', 'max:100'],
            'foto_btpa' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'hapus_foto' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['hapus_foto' => $this->boolean('hapus_foto')]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->umurHari($this->siklus()) > 60) {
                    $validator->errors()->add('tanggal', 'Tanggal terlalu jauh dari tanggal masuk siklus.');

                    return;
                }

                if ((float) $this->input('tonase_kg') / (int) $this->input('jumlah_ekor') > self::ABW_MAKS_KG) {
                    $validator->errors()->add('tonase_kg', 'Bobot rata-rata per ekor lebih dari '.self::ABW_MAKS_KG.' kg. Periksa jumlah ekor dan tonase (kg).');
                }
            },
        ];
    }

    /** Umur panen = hari umur pada tanggal panen (tanggal masuk dihitung hari ke-1). */
    public function umurHari(Siklus $siklus): int
    {
        $tanggal = Carbon::parse($this->input('tanggal'))->startOfDay();

        return (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays($tanggal) + 1;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'jumlah_ekor' => 'jumlah ekor',
            'tonase_kg' => 'tonase',
            'harga_per_kg' => 'harga per kg',
            'nomor_btpa' => 'nomor BTPA',
            'foto_btpa' => 'foto BTPA',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'foto_btpa.mimes' => 'Foto BTPA harus berupa JPG, PNG, atau WebP.',
            'foto_btpa.max' => 'Foto BTPA maksimal 4 MB.',
        ];
    }
}
