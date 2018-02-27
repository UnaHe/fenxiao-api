<?php

namespace App\Listeners;

use App\Events\CalculateOrderEvent;
use App\Models\SystemPids;
use App\Models\UserOrderIncome;
use App\Models\UserTree;
use App\Services\UserGradeService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * 订单分成
 * Class CalculateOrderListener
 * @package App\Listeners
 */
class CalculateOrderListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CalculateOrderEvent  $event
     * @return void
     */
    public function handle(CalculateOrderEvent $event)
    {
        //交由后台管理实现
    }
}
