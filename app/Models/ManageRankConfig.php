<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class ManageRankConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'manage_rank_config';
    
    /**
     * 设置缓存
     */
    public static function SetListCache()
    {
        $key = 'ManageRankConfigList';
        $MyRedis = new MyRedis();
        $list = self::query()
            ->orderBy('lv', 'asc')
            ->get(['lv','name','reward_usdt','backend_set','is_show'])
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
        $key = 'ManageRankConfigList';
        $MyRedis = new MyRedis();
        $list = $MyRedis->get_key($key);
        if (!$list) {
            return self::SetListCache();
        } else {
            return unserialize($list);
        }
    }
}
