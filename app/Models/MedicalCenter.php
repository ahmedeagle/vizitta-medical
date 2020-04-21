<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalCenter extends Model
{
    protected $table = 'medical_center';
    public $timestamps = true;
    protected $fillable = [/*'user_id',*/ 'name', 'branch_count', 'responsible_name', 'responsible_mobile'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'medical_center_cities_pivot', 'medical_center_id', 'city_id');
    }

    public function specifications()
    {
        return $this->belongsToMany(Specification::class, 'medical_center_specifications_pivot', 'medical_center_id', 'specification_id');
    }
}
