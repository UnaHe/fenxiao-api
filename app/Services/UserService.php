<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/18
 * Time: 15:51
 */
namespace App\Services;

use App\Helpers\BaseConvert;
use App\Models\SystemPids;
use App\Models\UserLoginToken;
use App\Models\UserReferralCode;
use App\Models\UserTree;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class UserService
{
    /**
     * 注册用户
     * @param $userName
     * @param $password
     * @param $inviteCode
     * @throws \Exception
     */
    public function registerUser($userName, $password, $inviteCode){
        $nowTime = Carbon::now();
        //上级用户id
        $parentUserId = 0;

        if($inviteCode){
            $code = (new UserReferralCode())->getByCode($inviteCode);
            if($code){
                $parentUserId = $code['user_id'];
            }
        }

        $rightValue = UserTree::where("user_id", $parentUserId)->pluck("right_val")->first();
        if(!$rightValue){
            Log::error("pytao_user_tree 无user_id=0的初始化数据");
            throw new \Exception("系统错误");
        }

        DB::beginTransaction();
        try{
            //创建用户
            $newUser = User::create([
                'mobile' => $userName,
                'password' => bcrypt($password),
                'reg_time' => $nowTime,
                'reg_ip' => Request::ip(),
            ]);

            if(!$newUser){
                throw new \LogicException("注册失败");
            }

            $userId = $newUser->id;

            //1、移动左节点
            UserTree::where([['left_val', ">=", $rightValue]])->increment("left_val", 2);
            //2、移动右节点
            UserTree::where([['right_val', ">=", $rightValue]])->increment("right_val", 2);

            //3、插入节点
            if(!UserTree::create([
                'user_id' => $userId,
                'parent_id' => $parentUserId,
                'left_val' => $rightValue,
                'right_val' => $rightValue+1,
            ])){
                Log::error("pytao_user_tree 插入节点失败");
                throw new \Exception("系统错误");
            }

            //使用一个pid
            if(!SystemPids::where("user_id", 0)->limit(1)->update(['user_id'=>$userId])){
                Log::error("分配系统PID失败");
                throw new \Exception("系统错误");
            }

            //创建用户邀请码
            (new UserReferralCode())->createCode($userId);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            $error = "注册失败";
            if($e instanceof \LogicException){
                $error = $e->getMessage();
            }else{
                if(User::where('mobile', $userName)->exists()){
                    $error = '该用户已注册';
                }
            }
            throw new \Exception($error);
        }
    }


    /**
     * 用户登录
     * @param $mobile
     * @param $password
     */
    public function login($mobile, $password){
        $user = User::where(['mobile' => $mobile])->first();
        if(!$user){
            throw new \Exception("用户不存在");
        }

        if(!Hash::check($password, $user['password'])){
            throw new \Exception("用户名或密码错误");
        }

        $createTime = Carbon::now();
        $expireTime = Carbon::now()->addDay(15);
        $loginToken = UserLoginToken::create([
            'user_id' => $user->id,
            'create_time' => $createTime,
            'expire_time' => $expireTime,
        ]);

        $data = array(
            "sub" => $loginToken->id,
            "exp" => $expireTime->getTimestamp(),
            "iat" => $createTime->getTimestamp(),
        );

        $token = JWT::encode($data, config('app.key'));

        return [
            'access_token' => $token,
            'expire_time' => $data['exp'],
        ];
    }

    /**
     * 验证登录token
     * @param $token
     * @return bool| User
     */
    public function checkLoginToken($token){
        try{
            $data = JWT::decode($token, config('app.key'), array('HS256'));

            $user = User::query()->from((new User())->getTable()." as user")
                ->leftJoin((new UserLoginToken())->getTable().' as token', 'user.id', '=', 'token.user_id')
                ->select("user.*")
                ->where("token.id", $data->sub)->first();

            return $user;
        }catch (\Exception $e){
        }

        return false;
    }


    /**
     * 修改密码
     * @param $userName
     * @param $password
     * @throws \Exception
     */
    public function modifyPassword($userName, $password){
        try{
            $user = User::where("mobile", $userName)->first();
            if(!$user){
                throw new \LogicException("用户不存在");
            }
            $user['password'] = bcrypt($password);

            $user->save();
        }catch (\Exception $e){
            if($e instanceof \LogicException){
                $error = $e->getMessage();
            }else{
                $error = '修改密码失败';
            }
            throw new \Exception($error);
        }
    }

    /**
     * 获取推荐码
     * @param User $user
     * @return null
     */
    public function getReferralCode($user){
        $code = (new UserReferralCode())->getByUserId($user->id);
        if($code){
            return $code['referral_code'];
        }
        return null;
    }

    /**
     * 通过推荐码获取用户信息
     * @param $code
     * @return User
     */
    public function getUserByReferralCode($code){
        $code = (new UserReferralCode())->getByCode($code);
        if(!$code){
            return false;
        }
        return User::find($code['user_id']);
    }

}
