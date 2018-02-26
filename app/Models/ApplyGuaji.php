<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 挂机续费申请
 * Class ApplyGuaji
 * @package App\Models
 */
class ApplyGuaji extends Model
{
    protected $table = "pytao_apply_guaji";
    protected $guarded = ['id'];
    public $timestamps = false;
}