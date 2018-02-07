<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2018/2/7
 * Time: 15:09
 */

namespace App\Services;


use App\Helpers\QueryHelper;
use App\Models\AlimamaOrder;
use App\Models\UserOrderIncome;

class AlimamaOrderService
{
    /**
     * 查询用户订单列表
     * @param $orderUserId
     * @param $orderState
     */
    public function userOrderList($userId, $orderUserId, $orderState){
        if($orderUserId != $userId){
            if(!(new TeamService())->userInTeam($userId, $orderUserId, 2)){
                throw new \Exception("查询用户不在团队内");
            }
        }

        $query = AlimamaOrder::query()->from((new AlimamaOrder())->getTable() . ' as aliorder');
        $query->leftJoin((new UserOrderIncome())->getTable() . ' as income', 'aliorder.id', '=', 'income.order_id')
            ->where('income.order_user_id', $orderUserId)
            ->where('income.user_id', $orderUserId)
            ->orderBy("create_time", "desc");

        $orderStateCondition = [];
        switch ($orderState){
            case 1:break;
            case 2:{
                $orderStateCondition = [
                    AlimamaOrder::ORDERSTATE_PAYED,
                    AlimamaOrder::ORDERSTATE_SETTLE,
                ];
                break;
            }
            case 21:{
                $orderStateCondition = [
                    AlimamaOrder::ORDERSTATE_PAYED,
                ];
                break;
            }
            case 22:{
                $orderStateCondition = [
                    AlimamaOrder::ORDERSTATE_SETTLE,
                ];
                break;
            }
            case 3:{
                $orderStateCondition = [
                    AlimamaOrder::ORDERSTATE_INVALID,
                ];
                break;
            }
        }

        if($orderStateCondition){
            $query->whereIn('aliorder.order_state', $orderStateCondition);
        }


        $query->select([
            "aliorder.id",
            "aliorder.goods_id",
            "aliorder.goods_title",
            "aliorder.shop_name",
            "aliorder.create_time",
            "aliorder.order_state",
            "aliorder.pay_money",
            "aliorder.predict_money",
            "aliorder.settle_money",
            "aliorder.predict_income",
            "income.share_rate as user_rate"
        ]);

        $orderList = (new QueryHelper())->pagination($query)->get()->toArray();

        $userService = new UserService();

        foreach ($orderList as &$order){
            //订单状态
            $order['order_state_str'] = AlimamaOrder::getOrderStateStr($order['order_state']);
            //预估收入
            $order['predict_money'] = round($userService->getUserMoney($order['predict_money'], $order['user_rate']), 2);
            //预估结算收入
            $order['predict_income'] = round($userService->getUserMoney($order['predict_income'], $order['user_rate']), 2);
        }

        return $orderList;
    }
}