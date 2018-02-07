<?php

namespace App\Http\Controllers;

use App\Helpers\UtilsHelper;
use App\Models\SysConfig;
use App\Models\WechatDomain;
use App\Services\StatisticsService;
use App\Services\SysConfigService;
use App\Services\WechatPageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatisticsController extends Controller
{
    /**
     * 查询指定日期订单信息
     * @param Request $request
     */
    public function day(Request $request){
        $day = $request->get('day', 'today');
        try{
            $day = new Carbon($day);
        }catch (\Exception $e){
            return $this->ajaxError("时间格式错误");
        }

        $userId = $request->user()->id;
        $data = (new StatisticsService())->day($day, $userId);

        return $this->ajaxSuccess($data);
    }

    /**
     * 查询月收益数据
     * @param Request $request
     * @return static
     */
    public function month(Request $request){
        $userId = $request->user()->id;
        $data = (new StatisticsService())->month($userId);

        return $this->ajaxSuccess($data);
    }

    /**
     * 查询团队月收益数据
     * @param Request $request
     * @return static
     */
    public function teamMonth(Request $request){
        $userId = $request->user()->id;
        $data = (new StatisticsService())->teamMonth($userId);

        return $this->ajaxSuccess($data);
    }
}
