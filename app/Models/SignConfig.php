<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class SignConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'sign_config';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'SignConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->where('is_del', 0)
            ->orderBy('sort', 'asc')
            ->orderBy('price', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id','price','sign_power_rate','sort'])
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
        $key = 'SignConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
    
    /**
     * 设置缓存
     */
    public static function SetAllListCache()
    {
        $key = 'SignConfigAllList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->orderBy('sort', 'asc')
            ->orderBy('price', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id','price','sign_power_rate','sort'])
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
    public static function GetAllListCache()
    {
        $key = 'SignConfigAllList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
    
}
