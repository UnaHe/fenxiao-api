<?php

namespace App\Http\Controllers;

use App\Helpers\ValidateHelper;
use App\Services\ApplyUpgradeService;
use Illuminate\Http\Request;

class ApplyUpgradeController extends Controller
{
    /**
     * 提交直升等级申请
     * @param Request $request
     */
    public function addApply(Request $request){
        $grade = $request->post('grade');
        $mobile = $request->post('mobile');
        $alipayAccount = $request->post('alipay_account');
        if(!$grade || !$mobile || !$alipayAccount){
            return $this->ajaxError("参数错误");
        }

        if(!ValidateHelper::isMobile($mobile)){
            return $this->ajaxError("手机号码格式错误");
        }
        if(!ValidateHelper::isAlipayAccount($alipayAccount)){
            return $this->ajaxError("支付宝账号格式错误");
        }

        if(!(new ApplyUpgradeService())->addApply($request->user()->id, $grade, $mobile, $alipayAccount)){
            return $this->ajaxError("申请失败");
        }

        return $this->ajaxSuccess();
    }
}
