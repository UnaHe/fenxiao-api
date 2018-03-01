<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2018/3/1
 * Time: 14:11
 */

namespace App\Helpers;


class ValidateHelper
{
    /**
     * 验证是否是邮箱
     * @param $string
     */
    public static function isEmail($string){
        return preg_match("/^\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}$/", $string);
    }

    /**
     * 验证是否是手机
     * @param $string
     */
    public static function isMobile($string){
        return preg_match("/^(13|14|15|17|18|19)[0-9]{9}$/", $string);
    }

    /**
     * 支付宝账号验证
     * @param $string
     */
    public static function isAlipayAccount($string){
        return self::isMobile($string) || self::isEmail($string);
    }

    /**
     * 是否是中文姓名
     * @param $string
     * @return int
     */
    public static function isChineseName($string){
        return preg_match("/^[\x7f-\xff]{4,}$/", $string);
    }
}