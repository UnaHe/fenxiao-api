<?php

namespace App\Http\Controllers;

use App\Events\RegisterUserEvent;
use App\Helpers\UrlHelper;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * 用户登录
     * @param Request $request
     * @return static
     */
    public function login(Request $request){
        $mobile = $request->post('mobile');
        $password = $request->post('password');

        if (!$mobile || !$password) {
            return $this->ajaxError("参数错误");
        }
        try{
            $token = (new UserService())->login($mobile, $password);
        }catch(\Exception $e){
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess($token);
    }

    /**
     * 用户注册
     */
    public function register(Request $request){
        $inviteCode = $request->post('invite_code');
        $mobile = $request->post('mobile');
        $password = $request->post('password');
        $codeId = $request->post('codeId');
        $captcha = $request->post('captcha');

        if(!$mobile || !$password || !$codeId){
            return $this->ajaxError("参数错误");
        }
        if(!preg_match('/^1\d{10}$/', $mobile)){
            return $this->ajaxError('请输入正确的手机号码');
        }
        if(strlen($password) < 6){
            return $this->ajaxError('密码长度至少为6位');
        }

        if(!(new CaptchaService())->checkSmsCode($codeId, $captcha)){
            return $this->ajaxError("验证码错误");
        }

        try{
            (new UserService())->registerUser($mobile, $password, $inviteCode);
        }catch (\Exception $e){
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess();
    }

    /**
     * 发送注册验证码
     * @param Request $request
     * @return static
     */
    public function registerSms(Request $request){
        $mobile = $request->post('mobile');
        if(!preg_match('/^1\d{10}$/', $mobile)){
            return $this->ajaxError('请输入正确的手机号码');
        }

        $codeId = (new CaptchaService())->registerSms($mobile);
        if(!$codeId){
            return $this->ajaxError("短信发送失败");
        }

        return $this->ajaxSuccess(['codeId' => $codeId]);
    }

    /**
     * 修改密码验证码
     * @param Request $request
     * @return static
     */
    public function modifyPasswordSms(Request $request){
        $mobile = $request->post('mobile');
        if(!preg_match('/^1\d{10}$/', $mobile)){
            return $this->ajaxError('请输入正确的手机号码');
        }

        $codeId = (new CaptchaService())->modifyPasswordSms($mobile);
        if(!$codeId){
            return $this->ajaxError("短信发送失败");
        }

        return $this->ajaxSuccess(['codeId' => $codeId]);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return static
     */
    public function modifyPassword(Request $request){
        $userName = $request->post('username');
        $password = $request->post('password');
        $codeId = $request->post('codeId');
        $captcha = $request->post('captcha');

        if(!$userName || !$password || !$codeId){
            return $this->ajaxError("参数错误");
        }
        if(!preg_match('/^1\d{10}$/', $userName)){
            return $this->ajaxError('请输入正确的手机号码');
        }
        if(strlen($password) < 6){
            return $this->ajaxError('密码长度至少为6位');
        }

        if(!(new CaptchaService())->checkSmsCode($codeId, $captcha)){
            return $this->ajaxError("验证码错误");
        }

        try{
            (new UserService())->modifyPassword($userName, $password);
        }catch (\Exception $e){
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess();
    }

    /**
     * 邀请注册信息
     * @param Request $request
     * @return static
     */
    public function inviteInfo(Request $request){
        // 获取当前用户推荐码.
        $code = (new UserService())->getReferralCode($request->user());
        if(!$code){
            return $this->ajaxError("推荐码未设置");
        }

        // 拼接邀请链接
        $longUrl = 'http://'.config('domains.pytao_domains').'/register?u='.$code."&t=".time();
        // 短链接.
        $shortUrl = (new UrlHelper())->shortUrl($longUrl);

        // 响应邀请链接.
        $url = [
            'reg_long' => $longUrl,
            'reg_short' => $shortUrl,
            'code' => $code
        ];

        return $this->ajaxSuccess($url);
    }


}
