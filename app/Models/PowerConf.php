<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class PowerConf extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'power_conf';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'PowerConfList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->get(['id','usdt','power','day','power_price','usdt_power_price','image'])
            ->toArray();
        if ($list) {
            $MyRedis->set_key($key, serialize($list));
            //设置单个价格
            foreach ($list as $val) {
                $itemKey = 'PowerConfItem:'.$val['id'];
                $MyRedis->set_key($itemKey, serialize($val));
            }
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
        $key = 'PowerConfList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
    
    /**
     * 获取缓存
     */
    public static function GetItemCache($type=1)
    {
        $key = 'PowerConfItem:'.$type;
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetItemCache($type);
        } else {
            return unserialize($list);
        }
    }
    
    /**
     * 设置缓存
     */
    public static function SetItemCache($type=1)
    {
        $key = 'PowerConfItem:'.$type;
        $MyRedis = new MyRedis();
        $list = PowerConf::query()
            ->where('type', $type)
            ->first(['usdt','power','power_price','type']);
        if ($list) {
            $list = $list->toArray();
            $MyRedis->set_key($key, serialize($list));
            return $list;
        }
        if ($MyRedis->exists_key($key)) {
            $MyRedis->del_lock($key);
        }
        return [];
    }
    
    
}
