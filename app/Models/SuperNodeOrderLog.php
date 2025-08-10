<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class SuperNodeOrderLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'super_node_order_log';
    
}
