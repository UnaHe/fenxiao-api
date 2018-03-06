<?php

namespace App\Http\Controllers;
use App\Services\BannerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Services\TaobaoService;

/**
 * banner广告
 * Class BannerController
 * @package App\Http\Controllers
 */
class BannerController extends Controller
{
    /**
     * 获取指定位置广告
     */
    public function getBanner(Request $request, $position){
        $userId = $request->user()->id;
        if(!$position){
            return $this->ajaxError("参数错误");
        }

        $data = (new BannerService())->getBanner($userId, $position);
        return $this->ajaxSuccess($data);
    }

}
