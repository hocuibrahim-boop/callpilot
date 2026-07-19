<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basvuru extends Model
{
    protected $table = 'basvurular';

    protected $fillable = [
        'ad', 'ofis', 'telefon', 'eposta', 'paket',
        'danisman_sayisi', 'mesaj', 'durum', 'kaynak',
    ];
}
