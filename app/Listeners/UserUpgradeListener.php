<?php

namespace App\Listeners;

use App\Events\RegisterUserEvent;
use App\Models\Grade;
use App\Models\User;
use App\Models\UserTree;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * 用户升级程序
 * Class UserUpgradeListener
 * @package App\Listeners
 */
class UserUpgradeListener implements ShouldQueue
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
     * @param  RegisterUserEvent  $event
     * @return void
     */
    public function handle(RegisterUserEvent $event)
    {
        //交由后台管理实现
    }
}
