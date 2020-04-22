<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminWebToken extends Model
{
    protected $table = 'admin_browser_web_tokens';
    public $timestamps = false;

    protected $fillable = ['admin_id', 'token'];

    public function admin()
    {
        return $this->belongsTo(Manager::class, 'admin_id');
    }
}
