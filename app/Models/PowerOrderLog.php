<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class PowerOrderLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'power_order_log';
    
}
