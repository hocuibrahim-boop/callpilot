<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeadNote extends Model {
    protected $fillable = ['lead_id','user_id','metin','ses_url','transkript','ai_ozet'];
    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }
}
