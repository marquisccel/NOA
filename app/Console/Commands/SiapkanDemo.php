<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\SimulasiSeeder;
use Illuminate\Console\Command;

/** Menyiapkan akun dan data demo bila belum ada (dipakai saat penayangan pertama kali). */
class SiapkanDemo extends Command
{
    protected $signature = 'noa:siapkan-demo {--paksa : Buat ulang data demo walau sudah ada}';

    protected $description = 'Isi data simulasi untuk akun demo bila belum ada.';

    public function handle(): int
    {
        $ada = User::where('email', config('noa.akun_demo.plasma'))->exists();

        if ($ada && ! $this->option('paksa')) {
            $this->info('Data demo sudah ada; dilewati.');

            return self::SUCCESS;
        }

        $this->callSilent('db:seed', ['--class' => SimulasiSeeder::class, '--force' => true]);
        $this->info('Data demo siap: '.config('noa.akun_demo.plasma'));

        return self::SUCCESS;
    }
}
