<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reminder extends Model {
    protected $fillable = ['lead_id','user_id','zaman','not_text','durum','bildirim_gonderildi'];
    protected $casts = ['zaman' => 'datetime', 'bildirim_gonderildi' => 'boolean'];
    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }
}
