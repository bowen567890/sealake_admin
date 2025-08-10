<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class PointConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'point_config';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'PointConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->where('is_del', 0)
            ->orderBy('usdt_num', 'asc')
            ->orderBy('point', 'asc')
            ->get(['id','usdt_num','point'])
            ->toArray();
        if ($list) {
            $MyRedis->set_key($key, serialize($list));
            return $list;
        }
        if ($MyRedis->exists_key($key)) {
            $MyRedis->del_lock($key);
        }
        return [];
    }
    
    /**
     * 获取缓存
     */
    public static function GetListCache()
    {
        $key = 'PointConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
}
