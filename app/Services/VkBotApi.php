<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class VkBotApi implements IBotApi
{
    private string $token;

    public function __construct(string $token){
        $this->token = $token;
    }

    public function get_message(Request $request): Message|null
    {
        if($request['type'] == 'message_new' && $request['secret'] == Config::get('services.vkapi.secret')){
            $user = User::firstOrNew(['uid' => $request['object']['message']['peer_id'], 'type' => 'vk']);
            $user->save();
            return new Message('vk', $user, $request['object']['message']['text']);
        }
        return null;
    }

    public function send_message(User $user, string $text, array $keyboard = []): void
    {
        $compiled_keyboard = '';
        if(count($keyboard) > 0) $compiled_keyboard = $this->make_keyboard($keyboard);
        $this->api_call('messages.send', ['peer_id' => $user->uid, 'random_id' => rand(1,10000), 'message' => $text, 'keyboard' => $compiled_keyboard]);
    }

    /**
     * @throws ConnectionException
     */
    private function api_call(string $method, array $params): PromiseInterface|Response
    {
        return Http::withHeader('Authorization', 'Bearer '.$this->token)->post('https://api.vk.com/method/'.$method.'?'.http_build_query($params).'&v=5.199');
    }

    public function make_keyboard(array $buttons): string
    {
        $final_buttons = [];
        for($i = 0; $i < count($buttons); $i++){
            foreach ($buttons[$i] as $button){
                $final_buttons[$i][] = [
                    'action' => [
                        'type' => 'text',
                        'label' => $button->getText(),
                        'payload' => '',
                    ],
                    'color' => $button->getColor(),
                ];
            }
        }
        $keyboard = [
            'one_time' => true,
            'inline' => false,
            'buttons' => $final_buttons,
        ];
        return json_encode($keyboard);
    }

    public function get_type(): string
    {
        return 'vk';
    }

    public function get_user_name(User $user): string
    {
        try{
            $api = $this->api_call('users.get', ['user_ids' => $user->uid, 'lang' => 'ru']);
            return $api['response'][0]['first_name'];
        } catch (\Exception $exception){
            return '';
        }
    }
}
