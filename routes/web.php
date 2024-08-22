<?php

use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::post('/strelkabot/vk', function (Request $request, BotService $botService){
    if($request['type'] == 'confirmation'){
        return response(Config::get('services.vkapi.confirmation_code'));
    }
    $botService->handle_message($request);
    return response('ok');
});

Route::post('/strelkabot/tg', function (Request $request, BotService $botService){
    $botService->handle_message($request);
});
