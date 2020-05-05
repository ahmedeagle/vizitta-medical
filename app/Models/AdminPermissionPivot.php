<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermissionPivot extends Model
{
    protected $table = 'admin_permissions_pivot';
    public $timestamps = false;

    protected $fillable = ['admin_id', 'permission_id', 'view', 'add', 'edit', 'delete'];

    public function admin()
    {
        return $this->belongsTo(Manager::class, 'admin_id');
    }

    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'permission_id');
    }
}
