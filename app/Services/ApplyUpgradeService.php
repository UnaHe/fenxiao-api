<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/24
 * Time: 10:51
 */
namespace App\Services;

use App\Models\ApplyUpgrade;
use Carbon\Carbon;

class ApplyUpgradeService
{
    /**
     * 提交直升等级申请
     * @param int $userId 用户id
     * @param int $type 升级类型
     * @param string $mobile 需要升级的账号
     * @param string $alipayAccount 付款支付宝账号
     * @return bool
     */
    public function addApply($userId, $type, $mobile, $alipayAccount){
        $isSuccess = ApplyUpgrade::create([
            'mobile' => $mobile,
            'alipay_account' => $alipayAccount,
            'type' => $type,
            'user_id' => $userId,
            'status' => 0,
            'add_time' => Carbon::now()
        ]);

        return $isSuccess ? true : false;
    }
}
