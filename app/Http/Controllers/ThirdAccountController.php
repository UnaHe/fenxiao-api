<?php

namespace App\Http\Controllers;

use App\Services\ThirdAccountService;
use Illuminate\Http\Request;

class ThirdAccountController extends Controller
{
    /**
     * 保存支付宝信息
     * @param Request $request
     * @return static
     */
    public function saveAlipay(Request $request){
        //支付宝实名
        $name = $request->post('name');
        //支付宝账号
        $account = $request->post('account');
        $userId = $request->user()->id;

        if(!$name || !$account){
            return $this->ajaxError("参数错误");
        }
        if(!(new ThirdAccountService())->saveAlipay($userId, $name, $account)){
            return $this->ajaxError("绑定失败");
        }

        return $this->ajaxSuccess();
    }

    /**
     * 查询绑定的支付宝
     * @param Request $request
     * @return static
     */
    public function getAlipay(Request $request){
        $userId = $request->user()->id;
        $data = (new ThirdAccountService())->getAlipay($userId);
        return $this->ajaxSuccess($data);
    }

}
