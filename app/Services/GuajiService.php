<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/24
 * Time: 10:51
 */
namespace App\Services;

use App\Models\ApplyGuaji;
use Carbon\Carbon;

class GuajiService
{
    /**
     * 提交挂机续费申请
     * @param int $userId 用户id
     * @param string $mobile 需要升级的账号
     * @param string $alipayAccount 付款支付宝账号
     * @return bool
     */
    public function addApply($userId, $mobile, $alipayAccount){
        $isSuccess = ApplyGuaji::create([
            'mobile' => $mobile,
            'alipay_account' => $alipayAccount,
            'user_id' => $userId,
            'status' => 0,
            'add_time' => Carbon::now()
        ]);

        return $isSuccess ? true : false;
    }
}
