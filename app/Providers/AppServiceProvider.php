<?php

namespace App\Providers;

use App\Console\DbCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // DB は abr-batch と共用しているので、DB を作り直すコマンドを 2 重に止める
        // 1. Laravel の仕組み（migrate:fresh・migrate:refresh・migrate:reset・migrate:rollback・db:wipe）
        DB::prohibitDestructiveCommands();
        // 2. コマンドが始まる前に名前を見て止める（migrate・db:seed なども）
        Event::listen(CommandStarting::class, DbCommandGuard::class);
    }
}
