<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/18
 * Time: 15:51
 */
namespace App\Services;

use App\Helpers\CacheHelper;
use App\Helpers\ErrorHelper;
use App\Helpers\GoodsHelper;
use App\Helpers\ProxyClient;
use App\Helpers\UrlHelper;
use App\Models\Banner;
use App\Models\Goods;
use App\Models\GoodsCategory;
use App\Services\Requests\CouponGet;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class TransferService
{
    private $topClient;

    public function __construct(){
        include_once app_path("Librarys/Taobao/TopSdk.php");
        $this->topClient = new \TopClient(config('taobao.appkey'), config('taobao.secretkey'));
        $this->topClient->format="json";
    }

    /**
     * 高效转链
     * @param $taobaoGoodsId 淘宝商品id
     * @param $pid 用户联盟PID
     * @param $token 用户授权token
     * @return mixed
     * @throws \Exception
     */
    public function transferLink($taobaoGoodsId, $pid, $token){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        $pids = explode('_',$pid);
        $req = new \TbkPrivilegeGetRequest;
        $req->setItemId($taobaoGoodsId);
        $req->setAdzoneId($pids[3]); //B pid 第三位
        $req->setPlatform("1");
        $req->setSiteId($pids[2]);//A pid 第二位
        $resp = $this->topClient->execute($req, $token);

        //转换失败
        if (!$resp){
            throw new \Exception("转链失败");
        }

        //判断结果
        if(isset($resp['code'])){
            if($resp['code'] == 26){
                throw new \Exception("授权过期", ErrorHelper::ERROR_TAOBAO_INVALID_SESSION);
            }

            if(isset($resp['sub_code'])) {
                if ('invalid-sessionkey' == $resp['sub_code']) {
                    //session过期
                    throw new \Exception("授权过期", ErrorHelper::ERROR_TAOBAO_INVALID_SESSION);
                } else if ('isv.item-not-exist' == $resp['sub_code']) {
                    //商品错误
                    throw new \Exception("宝贝已下架或非淘客宝贝", ErrorHelper::ERROR_TAOBAO_INVALID_GOODS);
                } else if ('isv.pid-not-correct' == $resp['sub_code']) {
                    //pid错误
                    throw new \Exception("PID错误", ErrorHelper::ERROR_TAOBAO_INVALID_PID);
                }
            }
            throw new \Exception("转链失败");
        }

        $result = $resp['result']['data'];
        //更新商品佣金
        if(isset($result['max_commission_rate'])){
            $time = Carbon::now();
            Goods::where("goodsid", $taobaoGoodsId)->update([
                'commission' => $result['max_commission_rate'],
                'commission_update_time' => $time,
            ]);
        }
        CacheHelper::setCache($result, 5);
        return $result;
    }


    /**
     * 高效转链
     * @param $taobaoGoodsId
     * @param $userId
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function transferLinkByUser($taobaoGoodsId, $userId){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        $token = (new TaobaoService())->getToken($userId);
        $pid = (new TaobaoService())->getPid($userId);
        if(!$token){
            throw new \Exception("未授权", ErrorHelper::ERROR_TAOBAO_INVALID_SESSION);
        }
        if(!$pid){
            throw new \Exception("PID错误", ErrorHelper::ERROR_TAOBAO_INVALID_PID);
        }
        try{
            $result = $this->transferLink($taobaoGoodsId, $pid, $token);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        CacheHelper::setCache($result, 5);
        return $result;
    }


    /**
     * 淘宝短链接sclick转换
     * @param $url 原始url
     * @return mixed
     */
    public function transferSclick($url){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        try{
            $req = new \TbkSpreadGetRequest;
            $requests = new \TbkSpreadRequest;
            $requests->url = $url;
            $req->setRequests(json_encode($requests));
            $resp = $this->topClient->execute($req);
            $result = (array)$resp;

            if(isset($result['code'])){
                switch($result['sub_code']){
                    case 'isv.appkey-not-exists':
                        $error = "官方接口数据出错，请稍后再试！";
                        break;
                    case 'PARAMETER_ERROR_TITLE_ILLEGAL':
                        $error = "标题中包含敏感词汇，请检查标题内容后重试。";
                        break;
                    default:
                        $error = "官方接口数据出错，请稍后再试！";
                        break;
                }
                throw new \Exception($error);
            }

            $data = $result['results']['tbk_spread'][0]['content'];
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

        CacheHelper::setCache($data, 5);
        return $data;
    }

    /**
     * 淘客链接转淘口令
     * @param $title
     * @param $url
     * @return mixed
     */
    public function transferTaoCode($title, $url, $pic=""){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        try{
            $req = new \TbkTpwdCreateRequest;
            $req->setUserId("1");
            $req->setText(trim($title, " \t\n\r\0\x0B@"));
            $req->setUrl($url);
            $req->setLogo($pic);
            $req->setExt("{}");
            $resp = $this->topClient->execute($req);
            $result = (array)$resp;
            $data = $result['data']['model'];
        }catch (\Exception $e){
            throw new \Exception('淘口令转换失败');
        }

        CacheHelper::setCache($data);
        return $data;
    }

    /**
     * 非淘客链接转淘口令
     * @param $title
     * @param $url
     * @param string $pic
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function transferCommonTaoCode($title, $url, $pic=""){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        try{
            $req = new \WirelessShareTpwdCreateRequest;
            $tpwd_param = new \GenPwdIsvParamDto;
            $tpwd_param->ext="{}";
            $tpwd_param->logo=$pic;
            $tpwd_param->url=$url;
            $tpwd_param->text=trim($title, " \t\n\r\0\x0B@");
            $tpwd_param->user_id="1";
            $req->setTpwdParam(json_encode($tpwd_param));
            $resp = $this->topClient->execute($req);
            $result = (array)$resp;
            $data = $result['model'];
        }catch (\Exception $e){
            throw new \Exception('淘口令转换失败');
        }

        CacheHelper::setCache($data);
        return $data;
    }

    /**
     * 商品转链
     */
    public function transferGoodsByUser($goodsId, $couponId, $title, $description, $pic, $priceFull, $couponPrice, $sellNum, $userId){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        try{
            $token = (new TaobaoService())->getToken($userId);
            $pid = (new TaobaoService())->getPid($userId);
            if(!$token){
                throw new \Exception("未授权", ErrorHelper::ERROR_TAOBAO_INVALID_SESSION);
            }
            if(!$pid){
                throw new \Exception("PID错误", ErrorHelper::ERROR_TAOBAO_INVALID_PID);
            }
            $data = $this->transferGoods($goodsId, $couponId, $title, $pic, $pid, $token);

            $goodsInfo = [
                'goods_id' => $goodsId,
                'tao_code' => $data['tao_code'],
                'url' => $data['url'],
                's_url' => $data['s_url'],
                'pic' => $pic,
                'title' => $title,
                'description' => $description,
                'coupon_price' => $couponPrice,
                'price_full' => $priceFull,
            ];
            $wechatUrl = (new WechatPageService())->createPage($goodsInfo, $userId);
            //使用短网址
            try{
                $wechatUrl = (new UrlHelper())->shortUrl($wechatUrl);
            }catch (\Exception $e){
            }
            $data['wechat_url'] = $wechatUrl;

            $shareData = [
                'title' => $title,
                'price' => $priceFull,
                'used_price' => bcsub($priceFull, $couponPrice, 2),
                'coupon_price' => $couponPrice,
                'description' => $description,
                'tao_code' => $data['tao_code'],
                'wechat_url' => $wechatUrl,
                'sell_num' => $sellNum,
            ];
            //分享描述
            $data['share_desc'] = (new GoodsService())->getShareDesc($shareData);

        }catch (\Exception $e){
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        CacheHelper::setCache($data, 5);
        return $data;
    }

    /**
     * 商品转链
     * @param $goodsId 淘宝商品id
     * @param $couponId 指定优惠券
     * @param $title 标题
     * @param $pid pid
     * @param $token 淘宝session
     * @return array
     * @throws \Exception
     */
    public function transferGoods($goodsId, $couponId, $title, $pic, $pid, $token){
        if($cache = CacheHelper::getCache()){
            return $cache;
        }

        try{
            $result = $this->transferLink($goodsId,$pid,$token);
            $url = $result['coupon_click_url'];
            //不是阿里妈妈券则指定优惠券id
            if(strlen($couponId) > 1){
                $url .= "&activityId=".$couponId;
            }
            //无券商品直接用商品链接
            if(!$couponId){
                $url = (new CouponGet())->initWithUlandUrl($url)->getItemClickUrl();
            }
            $url = UrlHelper::fixUrlPrefix($url);
            $slickUrl = $this->transferSclick($url);
            $taoCode = $this->transferTaoCode($title, $slickUrl, $pic);

            $data = [
                'goods_id' => $goodsId,
                'url' => $url,
                's_url' => $slickUrl,
                'tao_code' => $taoCode
            ];
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        CacheHelper::setCache($data, 5);
        return $data;
    }

    /**
     * 获取跳转地址
     * @param $url
     * @param null $referer
     * @return mixed
     */
    public function getRedirectUrl($url, $referer=null){
        static $client;
        if(!$client){
            $client = new ProxyClient();
        }
        $response = $client->request('GET', $url, [
            'verify' => false,
            'headers' => [
                'Referer' => $referer,
            ],
            RequestOptions::ALLOW_REDIRECTS => [
                'max'             => 10,        // allow at most 10 redirects.
                'strict'          => true,      // use "strict" RFC compliant redirects.
                'referer'         => true,      // add a Referer header
                'track_redirects' => true,
            ],
        ]);
        $redirectUriHistory = $response->getHeader('X-Guzzle-Redirect-History'); // retrieve Redirect URI history
        return array_pop($redirectUriHistory);
    }

    /**
     * 获取最终跳转链接地址
     * @param $url
     * @return mixed
     */
    public function getFinalUrl($url){
        if(strpos($url, 's.click.taobao.com/t?')){
            $url = $this->getRedirectUrl($url);
            return $this->getFinalUrl($url);
        }else if(strpos($url, 's.click.taobao.com/t_js?tu=')){
            parse_str(parse_url($url)['query'], $query);
            $tu = $query['tu'];
            $url = $this->getRedirectUrl($tu, $url);
            return $url;
        }else if(strpos($url, 's.click.taobao.com/')){
            $url = $this->getRedirectUrl($url);
            return $this->getFinalUrl($url);
        }else if(strpos($url, 'item.taobao.com/item.htm')){
            return $url;
        }else if(strpos($url, 'a.m.taobao.com/i')){
            preg_match("/a\.m\.taobao\.com\/i(\d+)/", $url, $matchItemId);
            return "http://item.taobao.com/item.htm?id=".$matchItemId[1];
        }else if(strpos($url, 'uland.taobao.com')){
            return $url;
        }else if(strpos($url, 'detail.tmall.com')){
            return $url;
        }else if(strpos($url, 'detail.ju.taobao.com')){
            preg_match("/detail\.ju\.taobao\.com.*?[\?&]item_id=(\d+)/", $url, $matchItemId);
            return "http://item.taobao.com/item.htm?id=".$matchItemId[1];
        }else if(strpos($url, 'tqg.taobao.com')){
            preg_match("/tqg\.taobao\.com.*?[\?&]itemId=(\d+)/", $url, $matchItemId);
            return "http://item.taobao.com/item.htm?id=".$matchItemId[1];
        }else{
            //默认查询关键字拼url
            if(preg_match("/(taobao|tmall)\.com.*?[\?&]itemId=(\d+)/", $url, $matchItemId)){
                return "http://item.taobao.com/item.htm?id=".$matchItemId[2];
            }else if(preg_match("/(taobao|tmall)\.com.*?[\?&]item_id=(\d+)/", $url, $matchItemId)){
                return "http://item.taobao.com/item.htm?id=".$matchItemId[2];
            }else if(preg_match("/(taobao|tmall)\.com.*?[\?&]id=(\d+)/", $url, $matchItemId)){
                return "http://item.taobao.com/item.htm?id=".$matchItemId[2];
            }
        }
    }

    /**
     * 解析淘口令
     * @param $taoCode
     * @return mixed
     */
    public function queryTaoCode($code, $isMiao, $isLink, $userId){
        if($cache = CacheHelper::getCache($code)){
            return $cache;
        }

        if(!$isMiao){
            if(!$isLink){
                $req = new \WirelessShareTpwdQueryRequest;
                $req->setPasswordContent($code);
                $resp = $this->topClient->execute($req);
                $resp = (array)$resp;
                if(isset($resp['code']) || (isset($resp['suc']) && $resp['suc'] == false)){
                    throw new \Exception("淘口令解析失败");
                }
                $lastUrl = $resp['url'];
            }else{
                $lastUrl = $code;
            }

            //获取最终跳转地址
            $lastUrl = $this->getFinalUrl($lastUrl);
        }else{
            $client = new ProxyClient(['cookie'=>true]);

            //喵口令解析
            $response = $client->get($code);
            if(!$response){
                throw new \Exception("喵口令解析失败");
            }
            $content = $response->getBody()->getContents();
            if(!preg_match("/\"itemId\":(\d+)/", $content, $matchItemId)){
                throw new \Exception("喵口令解析失败");
            }
            $lastUrl =  "http://item.taobao.com/item.htm?id=".$matchItemId[1];
        }

        if(!strpos($lastUrl, "uland.taobao.com")){
            if(preg_match("/item\.taobao\.com.*?[\?&]id=(\d+)/", $lastUrl, $matchItemId)){
                $matchItemId = $matchItemId[1];
            }else if(preg_match("/detail\.tmall\.com.*?[\?&]id=(\d+)/", $lastUrl, $matchItemId)){
                $matchItemId = $matchItemId[1];
            }

            if(!$matchItemId){
                throw new \Exception("淘口令解析失败");
            }
            $itemId = $matchItemId;

            $token = (new TaobaoService())->getToken($userId);
            $pid = (new TaobaoService())->getPid($userId);
            if(!$token){
                throw new \Exception("未授权", ErrorHelper::ERROR_TAOBAO_INVALID_SESSION);
            }
            if(!$pid){
                throw new \Exception("PID错误", ErrorHelper::ERROR_TAOBAO_INVALID_PID);
            }
            try{
                $result = $this->transferLink($itemId, $pid, $token);
                $lastUrl = $result['coupon_click_url'];

            }catch (\Exception $e){
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        //获取二合一详情信息
        $ulandDetail  = (new CouponGet())->initWithUlandUrl($lastUrl);
        $result = $ulandDetail->getResult();
        if(!$result){
            return false;
        }

        parse_str(parse_url($lastUrl)['query'], $lastUrlParams);
        $itemId = $ulandDetail->getItemId();
        $isTmall = $ulandDetail->getIsTmall();
        $sellerName = $ulandDetail->getShopName();
        $sellerIcon = $ulandDetail->getShopLogo();

        //从数据库获取信息填充
        $detail = (new GoodsService())->getByGoodsId($itemId);
        $detail = ($detail && $detail['is_del'] == 0) ? $detail : null;

        //优惠券id
        $couponId = isset($lastUrlParams['activityId']) ? $lastUrlParams['activityId'] : null;

        //是否使用数据库优惠券信息
        $isUseDbCoupon = !$couponId && $detail;

        //优惠券信息
        $couponMLink = $isUseDbCoupon ? $detail['coupon_m_link'] : null;
        $couponLink = $isUseDbCoupon ? $detail['coupon_link']: null;
        $couponPrice = $isUseDbCoupon ? $detail['coupon_price'] : $ulandDetail->getCouponPrice();
        $couponTime = $isUseDbCoupon ? $detail['coupon_time'] : $ulandDetail->getCouponEndTime();
        $couponPrerequisite = $isUseDbCoupon ? $detail['coupon_prerequisite'] : $ulandDetail->getCouponPrerequisite();
        $couponNum = $isUseDbCoupon ? $detail['coupon_num'] : 0;
        $couponOver = $isUseDbCoupon ? $detail['coupon_over'] : 0;
        $couponId = $isUseDbCoupon ? $detail['coupon_id'] : $couponId;

        $priceFull = $detail ? $detail['price_full'] : $ulandDetail->getPrice();
        $title = $detail ? $detail['title'] : $ulandDetail->getTitle();

        //佣金信息
        $commissionType = $detail ? $detail['commission_type'] : 0;
        $commission = $detail? $detail['commission'] : 0;
        $commissionMarketing = $detail? $detail['commission_marketing'] : 0;
        $commissionPlan = $detail? $detail['commission_plan'] : 0;
        $commissionBridge = $detail? $detail['commission_bridge'] : 0;

        //卖家信息
        $sellerId = $detail ? $detail['seller_id'] : null;
        $sellerName = (!$sellerName && $detail) ? $detail['seller_name'] : $sellerName;
        $sellerIcon = (!$sellerIcon && $detail) ? $detail['seller_icon_url'] : $sellerIcon;

        $isJuhuashuan = $detail ? $detail['is_juhuashuan'] : 0;
        $isTaoqianggou = $detail ? $detail['is_taoqianggou'] : 0;
        $isDeliveryFee = $detail ? $detail['is_delivery_fee'] : 0;

        $planLink = $detail ? $detail['plan_link'] : 0;
        $planApply = $detail ? $detail['plan_apply'] : 0;

        $catagoryId = $detail ? $detail['catagory_id'] : 0;
        $dsr = $detail ? $detail['dsr'] : 0;
        $des = $detail ? $detail['des'] : "";
        $goodsUrl = (new GoodsHelper())->generateTaobaoUrl($itemId, $isTmall);

        //从联盟查询
        $mamaDetail = (new AlimamaGoodsService())->detail($itemId);
        if($mamaDetail){
            try{
                if($mamaDetail['tkSpecialCampaignIdRateMap']){
                    $commission = max(array_values($mamaDetail['tkSpecialCampaignIdRateMap']));
                }
                $commission = max($commission, $mamaDetail['eventRate'], $mamaDetail['tkRate']);

                if(!$couponId && $mamaDetail['couponAmount']){
                    $couponTime = $mamaDetail['couponEffectiveEndTime']." 23:59:59";
                    $couponPrice = $mamaDetail['couponAmount'];
                    $couponPrerequisite = $mamaDetail['couponStartFee'];
                    $couponNum = $mamaDetail['couponTotalCount'];
                    $couponOver = $mamaDetail['couponLeftCount'];
                    $couponId = $mamaDetail['couponActivityId'] ?: 1;
                }
            }catch (\Exception $e){
                Log::error("查询联盟商品佣金失败， 商品id:".$itemId);
            }
        }

        if($sellerName == '天猫超市'){
            $couponNum = null;
            $couponOver = null;
            $couponLink = null;
            $couponPrice = 0;
            $couponPrerequisite = 0;
            $couponId = null;
            $couponMLink = null;
            $couponTime = null;
        }


        $data = [
            'goodsid' => $itemId,
            'goods_url' => $goodsUrl,
            'short_title' => $title,
            'title' => $title,
            'sell_num' => $ulandDetail->getSellNum(),
            'pic' => $ulandDetail->getPicUrl(),
            'price' => bcsub($priceFull, $couponPrice, 2),
            'price_full' => $priceFull,
            'coupon_time' => $couponTime,
            'coupon_price' => $couponPrice,
            'coupon_prerequisite' => $couponPrerequisite,
            'coupon_num' => $couponNum,
            'coupon_over' => $couponOver,
            'seller_name' => $sellerName,
            'seller_icon_url' => $sellerIcon,
            'is_tmall' => $isTmall,
            'coupon_id' => $couponId,
            'coupon_m_link'=> $couponMLink,
            'coupon_link'=> $couponLink,
            'catagory_id' => $catagoryId,
            'dsr' => $dsr,
            'seller_id' => $sellerId,
            'is_juhuashuan' => $isJuhuashuan,
            'is_taoqianggou' => $isTaoqianggou,
            'is_delivery_fee' => $isDeliveryFee,
            'des' => $des,
            'plan_link' => $planLink,
            'plan_apply' => $planApply,
            'commission_type' => $commissionType,
            'commission' => $commission,
            'commission_marketing' => $commissionMarketing,
            'commission_plan' => $commissionPlan,
            'commission_bridge' => $commissionBridge,
        ];

        $shareData = [
            'title' => $data['title'],
            'price' => $data['price_full'],
            'used_price' => $data['price'],
            'coupon_price' => $data['coupon_price'],
            'description' => $data['des'],
            'sell_num' => $data['sell_num'],
        ];
        //分享描述
        $data['share_desc'] = (new GoodsService())->getShareDesc($shareData);

        CacheHelper::setCache($data, 5, $code);
        return $data;
    }
}
