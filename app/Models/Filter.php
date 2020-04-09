<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    protected $table = 'filters';
    public $timestamps = true;

    protected $fillable = ['title_ar', 'title_en', 'operation', 'price','status'];

    protected $hidden = ['created_at', 'updated_at'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeAdminSelection($query)
    {
        return $query->select('id','title_ar','price','operation','status');
    }
    public function scopeSelection($query)
    {
        return $query->select('id','title_'.app()->getLocale().' as title','price','operation');
    }

    public function getOperation(){

        if($this -> operation  ==  0 ){
            return 'أقل من';
        }
        elseif($this -> operation  ==  1 ){
            return 'أكبر  من';
        }
        elseif($this -> operation  == 2 ){
            return ' يساوي';
        }
        elseif($this -> operation  ==  3 ){
            return 'الأكثر مبيعا';
        }
        elseif($this -> operation  ==  4){
            return ' الاكثر زياره ';
        }
        else
            return 'الاحدث';
    }

    public function getStatus(){

        return $this -> status == 0 ? 'غير مفعل'  : 'مفعل '  ;
    }


    public function getPriceAttribute($val){
      return   $val == null ? "" : $val ;
    }

}
