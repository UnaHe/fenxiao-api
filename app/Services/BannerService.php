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

class BannerService
{
    /**
     * 获取指定位置广告
     * @return mixed
     */
    public function getBanner($userId, $position){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        $data = Banner::select('name', 'pic', 'click_url')->where(['position'=>$position, 'is_delete'=>0])->get();

        $pid = "mm_99303416_22298718_74102081";
        if($userId){
            $pidInfo = (new UserService())->getPidInfo($userId);
            if($pidInfo){
                $pid = $pidInfo['pid'];
            }
        }
        $templateData = [];
        $templateData['user_id'] = $userId;
        $templateData['pid'] = $pid;

        foreach ($data as &$item){
            foreach ($templateData as $name=>$value){
                $item['click_url'] = str_replace('{'.$name.'}', $value, $item['click_url']);
            }
        }

        if($data){
            CacheHelper::setCache($data, 2);
        }
        return $data;
    }
}
