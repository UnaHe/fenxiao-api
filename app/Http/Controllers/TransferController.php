<?php

namespace App\Http\Controllers;

use App\Models\ChannelColumn;
use App\Models\Goods;
use App\Services\ChannelColumnService;
use App\Services\GoodsService;
use App\Services\TransferService;
use Illuminate\Database\Schema\Grammars\ChangeColumn;
use Illuminate\Http\Request;


/**
 * 转换工具
 * Class TransferController
 * @package App\Http\Controllers
 */
class TransferController extends Controller
{
    /**
     * 转链接
     */
    public function transferLink(Request $request){
        $taobaoGoodsId = $request->post('taobaoId');
        $couponId = $request->post('couponId');
        $title = $request->post('title');
        $description = $request->post('description');
        $pic = $request->post('pic');
        $priceFull = $request->post('priceFull');
        $couponPrice = $request->post('couponPrice', 0);
        $sellNum = $request->post('sell_num', 0);
        if(!$taobaoGoodsId || !$title || !$pic || !$priceFull){
            return $this->ajaxError("参数错误");
        }

        if(mb_strlen($title) < 5){
            return $this->ajaxError("商品标题不能少于5个字");
        }

        try{
            $data = (new TransferService())->transferGoodsByUser($taobaoGoodsId, $couponId, $title, $description, $pic, $priceFull, $couponPrice, $sellNum, $request->user()->id);
        }catch (\Exception $e){
            $errorCode = $e->getCode();
            return $this->ajaxError($e->getMessage(), $errorCode ?: 300);
        }
        return $this->ajaxSuccess($data);
    }

}
