<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SuperAdminOlustur extends Command
{
    protected $signature = 'callpilot:superadmin {eposta} {sifre}';
    protected $description = 'Yonetim paneli icin super admin hesabi olusturur veya gunceller';

    public function handle(): int
    {
        $eposta = $this->argument('eposta');
        $sifre  = $this->argument('sifre');

        $ofis = Office::firstOrCreate(
            ['ad' => 'CallPilot Yonetim'],
            ['plan' => 'sistem']
        );

        $user = User::where('eposta', $eposta)->first();

        if ($user) {
            $user->update([
                'rol'        => 'superadmin',
                'sifre_hash' => password_hash($sifre, PASSWORD_DEFAULT),
                'aktif'      => true,
            ]);
            $this->info("Guncellendi: {$eposta}");
        } else {
            User::create([
                'office_id'  => $ofis->id,
                'rol'        => 'superadmin',
                'ad'         => 'Sistem Yoneticisi',
                'eposta'     => $eposta,
                'sifre_hash' => password_hash($sifre, PASSWORD_DEFAULT),
                'api_token'  => Str::random(32),
                'aktif'      => true,
            ]);
            $this->info("Olusturuldu: {$eposta}");
        }

        return self::SUCCESS;
    }
}
