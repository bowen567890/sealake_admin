<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class InsuranceOrder extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'insurance_order';
    
}
