<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class LuckyLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'lucky_log';
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
