<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class PointOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'point_order';
    
}
