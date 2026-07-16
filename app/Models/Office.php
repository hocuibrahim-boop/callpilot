<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = ['ad', 'plan', 'tz', 'santral_saglayici', 'webhook_secret'];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
    public function calls(): HasMany { return $this->hasMany(Call::class); }
}
