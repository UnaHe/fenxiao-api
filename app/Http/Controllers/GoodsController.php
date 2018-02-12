<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\GoodsHelper;
use App\Services\ChannelColumnService;
use App\Services\CommissionService;
use App\Services\GoodsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


/**
 * 商品列表
 * Class GoodsController
 * @package App\Http\Controllers
 */
class GoodsController extends Controller
{
    /**
     * 获取商品列表
     */
    public function goodList(Request $request){
        //es滚动id
        $scrollId = $request->get('scroll_id');
        //商品排序
        $sort = $request->get('sort');
        $userId = $request->user()->id;

        $page = $request->get("page");
        $page = $page ?: 1;
        $limit = $request->get("limit");
        $limit = $limit ?: 20;

        $queryParams = [
            //商品分类
            'category' => $request->get('category'),
            //搜索关键字
            'keyword' => $request->get('keyword'),
            //淘抢购筛选
            'isTaoqianggou' => $request->get('tqg'),
            //聚划算筛选
            'isJuhuashuan' => $request->get('jhs'),
            //最低价格筛选
            'minPrice' => $request->get('min_price'),
            //最高价格筛选
            'maxPrice' => $request->get('max_price'),
            //天猫筛选
            'isTmall' => $request->get('is_tmall', 0),
            //最低佣金筛选
            'minCommission' => $request->get('min_commission', 0),
            //最低销量筛选
            'minSellNum' => $request->get('min_sell_num', 0),
            //最低券金额筛选
            'minCouponPrice' => $request->get('min_coupon_price'),
            //最高券金额筛选
            'maxCouponPrice' => $request->get('max_coupon_price'),
            //金牌卖家
            'isJpseller' => $request->get('is_jpseller', 0),
            //旗舰店
            'isQjd' => $request->get('is_qjd', 0),
            //海淘
            'isHaitao' => $request->get('is_haitao', 0),
            //极有家
            'isJyj' => $request->get('is_jyj', 0),
            //运费险
            'isYfx' => $request->get('is_yfx', 0),

            //视频商品
            'isVideo' => $request->get('is_video', 0),
        ];

        $result = (new GoodsService())->goodList($queryParams, $sort, $page, $limit, $scrollId, $userId);

        return $this->ajaxSuccess($result);
    }

    /**
     * 推荐商品列表
     * @param Request $request
     * @return static
     */
    public function recommendGoods(Request $request){
        //当前商品标题
        $title = $request->get('title');
        //淘宝商品id
        $taobaoGoodsId = $request->get('taobao_id');
        //是否推荐视频商品
        $isVideo = $request->get('is_video', 0);

        if(!$title){
            return $this->ajaxError("参数错误");
        }

        $list = (new GoodsService())->recommendGoods($title, $isVideo, $taobaoGoodsId, $request->user()->id);
        return $this->ajaxSuccess($list);
    }


    /**
     * 商品详情
     * @param $goodId
     * @return static
     */
    public function detail(Request $request, $goodId){
        $data = (new GoodsService())->detail($goodId, $request->user()->id);
        if(!$data){
            return $this->ajaxError("商品不存在", 404);
        }
        return $this->ajaxSuccess($data);
    }

    /**
     * 获取栏目商品列表
     * @param $columnCode 栏目代码
     * @return static
     */
    public function columnGoods(Request $request, $columnCode){
        //商品排序
        $sort = $request->get('sort');

        if(!(new ChannelColumnService())->getByCode($columnCode)){
            return $this->ajaxError("栏目不存在");
        }

        $queryParams = [
            //商品分类
            'category' => $request->get('category'),
            //淘抢购筛选
            'isTaoqianggou' => $request->get('tqg'),
            //聚划算筛选
            'isJuhuashuan' => $request->get('jhs'),
            //最低价格筛选
            'minPrice' => $request->get('min_price'),
            //最高价格筛选
            'maxPrice' => $request->get('max_price'),
            //天猫筛选
            'isTmall' => $request->get('is_tmall', 0),
            //最低佣金筛选
            'minCommission' => $request->get('min_commission', 0),
            //最低销量筛选
            'minSellNum' => $request->get('min_sell_num', 0),
            //最低券金额筛选
            'minCouponPrice' => $request->get('min_coupon_price'),
            //最高券金额筛选
            'maxCouponPrice' => $request->get('max_coupon_price')
        ];

        $params = $request->all();
        $params['column_code'] = $columnCode;
        $params['user_id'] = $request->user()->id;

        if(!$list = CacheHelper::getCache($params)){
            $list = (new GoodsService())->columnGoodList($columnCode, $queryParams, $sort, $request->user()->id);
            CacheHelper::setCache($list, 1, $params);
        }
        return $this->ajaxSuccess($list);
    }

    /**
     * 热搜词列表
     * @return static
     */
    public function hotKeyWord(){
        $data = ['耳机', '面膜', '口红', '保温杯', '卫衣', '毛衣女', '睡衣', '女鞋', '洗面奶', '充电宝'];
        return $this->ajaxSuccess($data);
    }

    /**
     * 全网搜索
     */
    public function queryAllGoods(Request $request){
        $page = intval($request->input("page", 1));
        $page = $page > 0 ? $page : 1;
        $limit = intval($request->input("limit", 20));
        $limit = $limit > 0 ? $limit : 20;
        //商品排序
        $sort = $request->get('sort');

        $queryParams = [
            //搜索关键字
            'keyword' => $request->get('keyword'),
            //是否有店铺优惠券
            'hasShopCoupon' => $request->get('has_shop_coupon', 0),
            //月成交转化率高于行业均值
            'isHighPayRate' => $request->get('is_high_pay_rate', 0),
            //天猫旗舰店
            'isTmall' => $request->get('is_tmall', 0),
            //最低销量筛选
            'minSellNum' => $request->get('min_sell_num', 0),
            //最低佣金筛选
            'minCommission' => $request->get('min_commission', 0),
            //最高佣金筛选
            'maxCommission' => $request->get('max_commission', 0),
            //最低价格筛选
            'minPrice' => $request->get('min_price'),
            //最高价格筛选
            'maxPrice' => $request->get('max_price'),
        ];

        if(!$queryParams['keyword']){
            return $this->ajaxError("参数错误");
        }

        $params = $request->all();
        $params['user_id'] = $request->user()->id;
        if(!$list = CacheHelper::getCache($params)){
            $list = (new GoodsService())->queryAllGoods($queryParams, $page, $limit, $sort, $request->user()->id);
            CacheHelper::setCache($list, 1, $params);
        }
        return $this->ajaxSuccess($list);
    }


}
