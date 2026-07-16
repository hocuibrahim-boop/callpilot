<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Office::exists()) return;

        $office = Office::create([
            'ad'   => 'Demo Emlak Ofisi',
            'plan' => 'ofis',
        ]);

        User::create([
            'office_id'   => $office->id,
            'rol'         => 'yonetici',
            'ad'          => 'Yönetici',
            'eposta'      => 'yonetici@ornek.com',
            'sifre_hash'  => password_hash('parola123', PASSWORD_DEFAULT),
            'api_token'   => Str::random(32),
            'aktif'       => true,
        ]);

        User::create([
            'office_id'   => $office->id,
            'rol'         => 'danisman',
            'ad'          => 'Ayşe Danışman',
            'eposta'      => 'ayse@ornek.com',
            'sifre_hash'  => password_hash('parola123', PASSWORD_DEFAULT),
            'api_token'   => Str::random(32),
            'aktif'       => true,
        ]);

        $this->command->info('Demo veriler oluşturuldu:');
        $this->command->info('  yonetici@ornek.com / parola123');
        $this->command->info('  ayse@ornek.com / parola123');
    }
}
