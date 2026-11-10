@props(["judul" => null, "kembali" => url("/")])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $judul ?? config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @page { size: A4; margin: 12mm; }
            @media print {
                body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .tanpa-cetak { display: none !important; }
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        {{-- Lembar cetak memakai hanya CSS print media (tanpa pustaka PDF), sesuai keputusan teknologi PRD. --}}
        <div class="tanpa-cetak max-w-4xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="{{ $kembali }}" class="text-base text-indigo-700 underline">Kembali</a>
            <button type="button" onclick="window.print()" class="min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Cetak</button>
        </div>

        <main class="max-w-4xl mx-auto bg-white p-6 sm:p-8 shadow-sm print:shadow-none print:p-0 print:max-w-none">
            {{ $slot }}
        </main>
    </body>
</html>
