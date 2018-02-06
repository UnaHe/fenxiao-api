<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 联盟订单
 * Class AlimamaOrder
 * @package App\Models
 */
class AlimamaOrder extends Model
{
    protected $table = "pytao_alimama_order";
    protected $guarded = ['id'];
    public $timestamps = false;

    /**
     * 订单付款
     */
    const ORDERSTATE_PAYED = 1;

    /**
     * 订单结算
     */
    const ORDERSTATE_SETTLE = 2;

    /**
     * 订单失效
     */
    const ORDERSTATE_INVALID = 3;

    /**
     * 订单成功
     * @todo 不清楚是什么意思
     */
    const ORDERSTATE_SUCCESS = 4;


}
