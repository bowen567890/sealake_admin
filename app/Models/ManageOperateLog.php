<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class ManageOperateLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'manage_operate_log';
    
    public function targetuser(){
        return $this->hasOne(User::class,'id','target_id');
    }
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
