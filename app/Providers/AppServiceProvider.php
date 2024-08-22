<?php

namespace App\Providers;

use App\Services\BotService;
use App\Services\CardService;
use App\Services\TgBotApi;
use App\Services\VkBotApi;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TgBotApi::class, function (Application $app){
            return new TgBotApi(config('services.tgapi.token'));
        });
        $this->app->singleton(VkBotApi::class, function (Application $app){
            return new VkBotApi(config('services.vkapi.access_token'));
        });

        switch(Request::path()){
            case 'strelkabot/vk':
                $this->app->singleton(BotService::class, function (Application $app){
                    $botService = new BotService(new CardService());
                    $botApi = $app->make(VkBotApi::class);
                    $botService->setBotApi($botApi);
                    return $botService;
                });
                break;
            case 'strelkabot/tg':
                $this->app->singleton(BotService::class, function (Application $app){
                    $botService = new BotService(new CardService());
                    $botApi = $app->make(TgBotApi::class);
                    $botService->setBotApi($botApi);
                    return $botService;
                });
                break;
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
