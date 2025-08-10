<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class MerchantOrderLog extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'merchant_order_log';
    
}
