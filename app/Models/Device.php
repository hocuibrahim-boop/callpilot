<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Device extends Model {
    protected $fillable = ['user_id','platform','push_token','izinler_json','son_gorulme'];
    protected $casts = ['izinler_json' => 'array', 'son_gorulme' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
