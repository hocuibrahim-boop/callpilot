<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $fillable = [
        'office_id', 'rol', 'ad', 'telefon', 'telefon_normal',
        'eposta', 'sifre_hash', 'api_token',
        'calisma_baslangic', 'calisma_bitis', 'aktif',
    ];

    protected $hidden = ['sifre_hash', 'api_token'];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class, 'atanan_user_id'); }
    public function calls(): HasMany { return $this->hasMany(Call::class); }
    public function reminders(): HasMany { return $this->hasMany(Reminder::class); }
}
