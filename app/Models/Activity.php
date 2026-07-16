<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {
    protected $fillable = ['office_id','user_id','lead_id','tip','veri_json'];
    protected $casts = ['veri_json' => 'array'];
}
