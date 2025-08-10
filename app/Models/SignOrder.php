<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class SignOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'sign_order';
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
