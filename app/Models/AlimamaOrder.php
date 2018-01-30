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
}
