<?php

/**
 * 无需登录访问的接口
 */
Route::middleware('auth.api')->namespace('App\Http\Controllers')->group(function (){
    /*
     * ================
     * 登录注册接口
     * ================
     */

    //注册验证码
    Route::post('/captcha/registerSms', "UserController@registerSms");
    //注册
    Route::post('/register', "UserController@register");
    //登录
    Route::post('/login', "UserController@login");
    //修改密码验证码
    Route::post('/captcha/modifyPasswordSms', "UserController@modifyPasswordSms");
    //修改密码，忘记密码
    Route::post('/modifyPassword', "UserController@modifyPassword");

    /*
     * ================
     * 商品信息接口
     * ================
     */
    //商品分类
    Route::get('/categorys', "CategoryController@getAllCategory");
    //商品列表
    Route::get('/goods', "GoodsController@goodList");
    //栏目商品列表
    Route::get('/columns/{code}/goods', "GoodsController@columnGoods");
    //推荐商品列表
    Route::get('/recommendGoods', "GoodsController@recommendGoods");
    //商品详情
    Route::get('/goods/{goodsId}', "GoodsController@detail")->where('goodsId', '[0-9]+');
    //秒杀时间点
    Route::get('/miaosha/times', "MiaoshaController@getTimes");
    //秒杀商品列表
    Route::get('/miaosha/goods', "MiaoshaController@getGoods");
    //全网搜索
    Route::get('/queryAllGoods', "GoodsController@queryAllGoods");


    /*
     * ================
     * 其他
     * ================
     */
    //服务器时间
    Route::get('/serverTime', "CommonController@getServerTime");
    //指定位置广告banner列表
    Route::get('/banners/{position}', "BannerController@getBanner");
    //热搜词
    Route::get('/hotKeyword', "GoodsController@hotKeyWord");

});



/**
 * 需要登录访问的接口列表
 */
Route::middleware('auth.api:force')->namespace('App\Http\Controllers')->group(function (){
    /*
     * ================
     * 用户信息
     * ================
     */
    //邀请注册信息
    Route::get('/user/inviteInfo', "UserController@inviteInfo");
    //用户基本信息
    Route::get('/user/simpleUserInfo', "UserController@simpleUserInfo");
    //用户余额查询
    Route::get('/user/balance', "UserController@balance");


    /*
     * ================
     * 商品
     * ================
     */
    //商品转链
    Route::post('/transferLink', "TransferController@transferLink");


    /*
     * ================
     * 报表
     * ================
     */
    //查询用户指定日期订单信息
    Route::get('/statistics/day', "StatisticsController@day");
    //查询用户月收益数据
    Route::get('/statistics/month', "StatisticsController@month");
    //查询团队月收益数据
    Route::get('/statistics/team/month', "StatisticsController@teamMonth");
    //查询用户团队奖励收入（团队提成）
    Route::get('/statistics/team/user_team_income', "StatisticsController@userTeamIncome");


//    //获取消息列表
//    Route::get('/messages', "MessageController@getMessageList");
//    //获取消息详情
//    Route::get('/messages/{messageId}', "MessageController@getMessage")->where('messageId', '[0-9]+');
//    //删除消息
//    Route::delete('/messages/{messageId}', "MessageController@deleteMessage")->where('messageId', '[0-9]+');
//    //获取未读消息数量
//    Route::get('/messages/unReadNum', "MessageController@unReadNum");



});

