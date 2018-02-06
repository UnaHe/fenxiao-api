<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2018/2/6
 * Time: 14:45
 */

namespace App\Services;
use App\Models\AlimamaOrder;
use App\Models\UserOrderIncome;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 统计报表
 * Class StatisticsService
 * @package App\Services
 */
class StatisticsService
{
    /**
     * 查询指定日期订单信息
     * @param Carbon $day 指定日期
     * @param int $userId 用户id
     */
    public function day($day, $userId){
        $query = AlimamaOrder::query()->from((new AlimamaOrder())->getTable().' as aliorder');
        $query->leftJoin((new UserOrderIncome())->getTable().' as income', 'aliorder.id', '=', 'income.order_id');
        $query->where([
            ['aliorder.create_time', '>=', $day->startOfDay()->toDateTimeString()],
            ['aliorder.create_time', '<=', $day->endOfDay()->toDateTimeString()],
            ['aliorder.order_state', '!=', AlimamaOrder::ORDERSTATE_INVALID],
            ['income.order_user_id', '=', $userId],
            ['income.user_id', '=', $userId]
        ]);

        $query->select(["aliorder.id", "aliorder.order_state", "aliorder.pay_money", "aliorder.predict_money", "income.share_rate as user_rate"]);

        $result = $query->get()->toArray();

        //系统扣款比例
        $systemRate = 0.16;
        //预估收入
        $preMoneyTotal = 0;
        //付款订单数
        $payOrderNum = 0;
        //付款金额
        $payMoneyTotal = 0;

        $orderIds = [];

        bcscale(5);
        foreach ($result as $item){
            if($item['order_state'] == AlimamaOrder::ORDERSTATE_PAYED || $item['order_state'] == AlimamaOrder::ORDERSTATE_SETTLE){
                //预估收入 = (订单效果预估 - 系统扣减手续费) * 用户分成比例
                $preMoney = bcmul(bcmul($item['predict_money'], (1 - $systemRate)), $item['user_rate']);
                $preMoneyTotal = bcadd($preMoneyTotal, $preMoney);

                if(!isset($orderIds[$item['id']])){
                    $payOrderNum++;
                    $payMoneyTotal = bcadd($payMoneyTotal, $item['pay_money']);
                    $orderIds[$item['id']] = $item;
                }
            }
        }

        $data = [
            'day' => $day->toDateString(),
            'predict_money' => round($preMoneyTotal, 2),
            'pay_order_num' => $payOrderNum,
            'pay_money_total' => round($payMoneyTotal, 2),
        ];

        return $data;
    }
}