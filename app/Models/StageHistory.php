<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StageHistory extends Model {
    protected $fillable = ['lead_id','eski_asama','yeni_asama','user_id'];
    public function lead() { return $this->belongsTo(Lead::class); }
}
