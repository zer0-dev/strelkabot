<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TgBotApi implements IBotApi
{
    private string $token;

    public function __construct(string $token){
        $this->token = $token;
    }

    public function get_message(Request $request): Message|null
    {
        if(isset($request['message'])){
            if(isset($request['message']['text']) && isset($request['message']['from'])){
                $user = User::firstOrNew(['uid' => $request['message']['from']['id'], 'type' => 'tg']);
                $user->username = $request['message']['from']['first_name'];
                $user->save();
                return new Message('tg', $user, $request['message']['text']);
            }
        }
        return null;
    }

    public function send_message(User $user, string $text, array $keyboard = []): void
    {
        $compiled_keyboard = '';
        if(count($keyboard) > 0) $compiled_keyboard = $this->make_keyboard($keyboard);
        $this->api_call('sendMessage', ['chat_id' => $user->uid, 'text' => $text, 'reply_markup' => $compiled_keyboard]);
    }

    private function api_call(string $method, array $params): PromiseInterface|Response{
        return Http::post('https://api.telegram.org/bot'.$this->token.'/'.$method.'?'.http_build_query($params));
    }

    public function make_keyboard(array $buttons): string
    {
        $final_buttons = [];
        for($i = 0; $i < count($buttons); $i++){
            foreach ($buttons[$i] as $button){
                $final_buttons[$i][] = [
                    'text' => $button->getText(),
                ];
            }
        }
        $keyboard = [
            'keyboard' => $final_buttons,
            'one_time_keyboard' => true,
            'resize_keyboard' => true,
        ];
        return json_encode($keyboard);
    }

    public function get_type(): string
    {
        return 'tg';
    }

    public function get_user_name(User $user): string
    {
        return $user->username;
    }
}
