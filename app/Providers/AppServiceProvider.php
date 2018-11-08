<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Log;
use Queue;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 队列失败
        Queue::failing(function ($connection, $job, $data) {
            // 通知团队失败的任务...
            Log::error($connection);
            Log::error('队列执行失败！', $data);
        });

        // 队列完成
        Queue::after(function ($connection, $job, $data) {
            Log::info('队列执行完成！', $data);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
