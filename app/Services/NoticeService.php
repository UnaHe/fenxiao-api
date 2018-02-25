<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/18
 * Time: 15:51
 */
namespace App\Services;

use App\Helpers\CacheHelper;
use App\Models\Banner;
use App\Models\Notice;
use Carbon\Carbon;

class NoticeService
{
    /**
     * 获取公告
     * @return mixed
     */
    public function getNotice(){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        $now = Carbon::now()->toDateTimeString();
        $data = Notice::select('title', 'add_time')->where([
            ['start_time', '<', $now],
            ['end_time', '>', $now],
            ['is_delete', '=', 0]
        ])->get();

        if($data){
            CacheHelper::setCache($data, 5);
        }
        return $data;
    }
}
