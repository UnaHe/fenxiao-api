<?php

namespace App\Http\Controllers;

use App\Helpers\UtilsHelper;
use App\Models\SysConfig;
use App\Models\WechatDomain;
use App\Services\AlimamaOrderService;
use App\Services\StatisticsService;
use App\Services\SysConfigService;
use App\Services\TeamService;
use App\Services\WechatPageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AlimamaOrderController extends Controller
{
    /**
     * 查询用户订单列表
     * @param Request $request
     * @return static
     */
    public function userOrderList(Request $request){
        //订单用户id
        $orderUserId = $request->get('user_id');
        //订单状态
        $orderState = $request->get('state', 1);
        //登录用户id
        $userId = $request->user()->id;

        $orderUserId = $orderUserId ?: $userId;
        try{
            $data = (new AlimamaOrderService())->userOrderList($userId, $orderUserId, $orderState);
        }catch (\Exception $e){
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess($data);
    }
}
