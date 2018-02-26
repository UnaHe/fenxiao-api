<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 直升等级申请
 * Class ApplyUpgrade
 * @package App\Models
 */
class ApplyUpgrade extends Model
{
    protected $table = "pytao_apply_upgrade";
    protected $guarded = ['id'];
    public $timestamps = false;
}