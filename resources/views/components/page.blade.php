@props(['lebar' => 'max-w-7xl'])

{{-- Pembungkus isi halaman: lebar maksimum dan jarak antarbagian; padding sisi hanya di ponsel (shell sudah memberi padding di md ke atas). --}}
<div {{ $attributes->merge(['class' => 'mx-auto w-full space-y-5 px-4 py-4 md:px-0 md:py-0 '.$lebar]) }}>
    {{ $slot }}
</div>
