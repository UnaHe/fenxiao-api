<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * 提现申请
 * Class Withdraw
 * @package App\Models
 */
class Withdraw extends Model
{
    protected $table = "pytao_withdraw";
    protected $guarded = ['id'];
    public $timestamps = false;

    /**
     * 申请中
     */
    const STATUS_APPLY = 0;

    /**
     * 提现成功
     */
    const STATUS_SUCCESS = 1;

    /**
     * 拒绝
     */
    const STATUS_REFUSE = 2;

}
