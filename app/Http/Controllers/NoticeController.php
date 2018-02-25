<?php

namespace App\Http\Controllers;
use App\Services\BannerService;
use App\Services\NoticeService;
use Illuminate\Http\Request;
use App\Services\TaobaoService;

/**
 * 公告管理
 * Class NoticeController
 * @package App\Http\Controllers
 */
class NoticeController extends Controller
{
    /**
     * 公告列表
     * @param Request $request
     * @param $position
     * @return static
     */
    public function getNotice(Request $request){
        $data = (new NoticeService())->getNotice();
        return $this->ajaxSuccess($data);
    }

}
