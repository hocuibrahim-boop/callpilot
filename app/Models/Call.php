<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Call extends Model {
    protected $fillable = ['office_id','user_id','lead_id','telefon','telefon_normal','yon','baslangic','sure_sn','kaynak','santral_cagri_id','kayit_durumu','donuldu'];
    protected $casts = ['baslangic' => 'datetime', 'donuldu' => 'boolean'];
    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }
}
