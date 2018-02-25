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
use App\Models\ThirdAccount;

class ThirdAccountService
{
    /**
     * 保存支付宝信息
     * @param int $userId 用户id
     * @param string $name 支付宝实名
     * @param string $account 支付宝账号
     * @return bool
     */
    public function saveAlipay($userId, $name, $account){
        $model = ThirdAccount::where(['user_id' => $userId])->first();
        if(!$model){
            $model = new ThirdAccount();
            $model['user_id'] = $userId;
        }

        $model['alipay_real_name'] = trim($name);
        $model['alipay_account'] = trim($account);

        if(!$model->save()){
            return false;
        }
        return true;
    }
}
