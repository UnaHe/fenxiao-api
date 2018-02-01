<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2018/2/1
 * Time: 11:13
 */

namespace App\Http\Controllers;


class CommonController extends Controller
{
    /**
     * 服务器时间
     * @return static
     */
    public function getServerTime(){
        return $this->ajaxSuccess(time());
    }

}