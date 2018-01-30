<?php

namespace App\Console\Commands;

use App\Models\AlimamaOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class SyncOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync_order {--from_time=} {--to_time=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步联盟订单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $nowDate = Carbon::now()->toDateString();
        //同步开始日期
        $fromTime = $this->option('from_time') ?: $nowDate;
        //同步结束日期
        $toTime = $this->option('to_time') ?: $nowDate;

        $downloadUrl = "http://pub.alimama.com/report/getTbkPaymentDetails.json?queryType=1&payStatus=&DownloadID=DOWNLOAD_REPORT_INCOME_NEW&startTime={$fromTime}&endTime={$toTime}";

        $cookie = $this->getCookie();
        if(!$cookie){
            throw new \Exception("获取cookie失败");
        }

        $client = (new \GuzzleHttp\Client([
            'headers' => [
                'cookie' => $cookie,
            ]
        ]));

        $dir = storage_path("download");
        if(!is_dir($dir)){
            @mkdir($dir);
        }
        $file = $dir."/order_".time().mt_rand(1000, 9999).".xls";
        $client->get($downloadUrl, ['save_to' => $file]);

//        $file = storage_path("download/1.xls");
        if(!is_file($file)){
            throw new \Exception("文件下载失败");
        }

        $content = Excel::load($file)->get()->toArray();

        foreach ($content as $item){
            $orderNo = $item['订单编号'];
            $data = [
                'order_no' => $orderNo,
                'order_state' => $this->getOrderState($item['订单状态']),
                'goods_id' => $item['商品id'],
                'goods_title' => $item['商品信息'],
                'goods_num' => $item['商品数'],
                'goods_price' => $item['商品单价'],
                'seller_name' => $item['掌柜旺旺'],
                'shop_name' => $item['所属店铺'],
                'income_rate' => floatval($item['收入比率']),
                'share_rate' => floatval($item['分成比率']),
                'pay_money' => $item['付款金额'],
                'settle_money' => $item['结算金额'],
                'settle_time' => $item['结算时间'],
                'predict_money' => $item['效果预估'],
                'predict_income' => $item['预估收入'],
                'commission_rate' => floatval($item['佣金比率']),
                'commission_money' => $item['佣金金额'],
                'subsidy_rate' => floatval($item['补贴比率']),
                'subsidy_money' => $item['补贴金额'],
                'subsidy_type' => $item['补贴类型'],
                'site_id' => $item['来源媒体id'],
                'adzone_id' => $item['广告位id'],
                'pay_platform' => $item['订单类型'],
                'platform' => $item['成交平台'],
                'create_time' => $item['创建时间'],
                'click_time' => $item['点击时间'],
                'sync_time' => Carbon::now(),
            ];

            try{
                $order = AlimamaOrder::where(['order_no' => $orderNo])->first();
                if($order){
                    if(!AlimamaOrder::where(['order_no' => $orderNo])->update($data)){
                        throw new \Exception("更新失败");
                    }
                }else{
                    if(!AlimamaOrder::create($data)){
                        throw new \Exception("添加失败");
                    };
                }
                $this->info($orderNo." 同步成功");
            }catch (\Exception $e){
                $this->error($orderNo." 同步失败");
            }
        }

        //删除文件
        @unlink($file);

    }

    /**
     * 获取cookie
     * @return bool
     */
    public function getCookie(){
        $result = (new \GuzzleHttp\Client())->get('http://47.92.94.162:8886/api?Key=1111&getname=cookie&skey=1111')->getBody()->getContents();
        $result = json_decode($result, true);
        if(json_last_error()){
            return false;
        }
        return $result['data'];
    }

    public function getOrderState($stateStr){
        $orderState = [
            '订单付款' => 1,
            '订单结算' => 2,
            '订单失效' => 3,
            '订单成功' => 4,
        ];

        return isset($orderState[$stateStr]) ? $orderState[$stateStr] : 0;
    }
}
