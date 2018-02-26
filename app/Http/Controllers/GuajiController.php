<?php

namespace App\Http\Controllers;

use App\Services\ApplyUpgradeService;
use App\Services\GuajiService;
use Illuminate\Http\Request;

class GuajiController extends Controller
{
    /**
     * 挂机续费申请
     * @param Request $request
     */
    public function addApply(Request $request){
        $mobile = $request->post('mobile');
        $alipayAccount = $request->post('alipay_account');
        if(!$mobile || !$alipayAccount){
            return $this->ajaxError("参数错误");
        }

        if(!(new GuajiService())->addApply($request->user()->id, $mobile, $alipayAccount)){
            return $this->ajaxError("申请失败");
        }

        return $this->ajaxSuccess();
    }
}
