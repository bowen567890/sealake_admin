<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class LuckyPool extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'lucky_pool';
    
}
