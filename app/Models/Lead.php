<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'office_id', 'atanan_user_id', 'telefon', 'telefon_normal',
        'ad_soyad', 'talep_tipi', 'gayrimenkul_turu', 'bolge', 'butce',
        'oncelik', 'asama', 'kaynak', 'kvkk_durumu',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function atananUser(): BelongsTo { return $this->belongsTo(User::class, 'atanan_user_id'); }
    public function calls(): HasMany { return $this->hasMany(Call::class); }
    public function notes(): HasMany { return $this->hasMany(LeadNote::class); }
    public function reminders(): HasMany { return $this->hasMany(Reminder::class); }
    public function stageHistory(): HasMany { return $this->hasMany(StageHistory::class); }
}
