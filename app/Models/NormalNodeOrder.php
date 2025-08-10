<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class NormalNodeOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'normal_node_order';
    
}
