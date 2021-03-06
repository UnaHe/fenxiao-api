<?php

namespace App\Http\Middleware;

use App\Services\SysConfigService;
use App\Services\UserService;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Cookie;

class ApiAuthenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, $guards)
    {
        $token = Cookie::get('token')?:$request->header('token');
        $token = $token ?: $request->input('token');
        if (!$token){
            $this->errorResponse($request, $guards);
        }

        $user = (new UserService())->checkLoginToken($token);
        if($user){
            // 将用户信息加入请求对象.
            $request->setUserResolver(function() use($user){
                return $user;
            });
            return;
        }

        $this->errorResponse($request, $guards);
    }

    /**
     * 错误返回
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @throws AuthenticationException
     */
    protected function errorResponse($request, $guards){
        if(in_array("force", $guards)){
            throw new AuthenticationException('Unauthenticated.', $guards);
        }else{
            $request->setUserResolver(function(){
                $user = new \stdClass();
                $user->id = 0;
                return $user;
            });
        }
    }
}
