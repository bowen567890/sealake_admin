<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserPoint extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'user_point';
    
    public function fromuser(){
        return $this->hasOne(User::class,'id','from_user_id');
    }
}
