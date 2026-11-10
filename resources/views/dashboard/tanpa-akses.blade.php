<x-app-layout title="Dashboard" :crumbs="[['Beranda', route('dashboard')], ['Dashboard']]">
    <x-page>
        <x-empty-state icon="lock" title="Akun ini belum punya akses ke data kandang"
            text="Peran akun Anda belum memiliki halaman di NOA. Hubungi pemilik kandang atau pengelola NOA." />
    </x-page>
</x-app-layout>
