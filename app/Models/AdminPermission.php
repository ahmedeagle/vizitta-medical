<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    protected $table = 'admin_permissions';
    public $timestamps = false;

    protected $fillable = ['name'];
}
