<?php

namespace App\Http\Controllers;

use App\Helpers\UtilsHelper;
use App\Models\SysConfig;
use App\Models\WechatDomain;
use App\Services\StatisticsService;
use App\Services\SysConfigService;
use App\Services\TeamService;
use App\Services\WechatPageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamController extends Controller
{
    /**
     * 团队成员列表
     * @param Request $request
     * @return static
     */
    public function userList(Request $request){
        $level = $request->get('level', 1);
        if($level > 2){
            return $this->ajaxError("只能查看两级团队成员列表");
        }

        $userId = $request->user()->id;
        $data = (new TeamService())->userList($userId, $level);

        return $this->ajaxSuccess($data);
    }
}
