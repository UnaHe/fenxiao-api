<?php

namespace App\Http\Controllers;

use App\Events\RegisterUserEvent;
use App\Helpers\UrlHelper;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

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
     * 注销登录
     * @param Request $request
     * @return static
     */
    public function logout(Request $request){
        $token = Cookie::get('token')?:$request->header('token');
        $token = $token ?: $request->input('token');

        if(!(new UserService())->logout($token)){
            return $this->ajaxError();
        }

        return $this->ajaxSuccess();
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

//        if(!$mobile || !$password || !$codeId){
//            return $this->ajaxError("参数错误");
//        }
//        if(!preg_match('/^1\d{10}$/', $mobile)){
//            return $this->ajaxError('请输入正确的手机号码');
//        }
//        if(strlen($password) < 6){
//            return $this->ajaxError('密码长度至少为6位');
//        }
//
//        if(!(new CaptchaService())->checkSmsCode($codeId, $captcha)){
//            return $this->ajaxError("验证码错误");
//        }

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
        $longUrl = 'http://'.config('domains.pytao_domains').'/ShareReg?code='.$code."&t=".time();
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

    /**
     * 用户基本信息
     * @param Request $request
     */
    public function simpleUserInfo(Request $request){
        $data = (new UserService())->simpleUserInfo($request->user()->id);
        return $this->ajaxSuccess($data);
    }

    /**
     * 用户余额
     * @param Request $request
     */
    public function balance(Request $request){
        $data = (new UserService())->balance($request->user()->id);
        return $this->ajaxSuccess([
            'balance' => $data
        ]);
    }

    /**
     * 提现申请
     * @param Request $request
     * @return static
     */
    public function withdraw(Request $request){
        //提现金额
        $amount = $request->post('amount');
        if(!is_numeric($amount)){
            return $this->ajaxError("参数格式错误");
        }

        if($amount <= 0){
            return $this->ajaxError("提现金额必须大于0");
        }

        try{
            if(!(new UserService())->withdraw($request->user()->id, $amount)){
                throw new \Exception("提现失败, 请重试");
            }
        }catch (\Exception $e){
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess();
    }

}
