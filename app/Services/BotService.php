<?php

namespace App\Services;

use App\Models\BotButton;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BotService
{
    private IBotApi $botApi;
    private CardService $cardService;
    private array $states;

    public function __construct(CardService $cardService){
        $this->cardService = $cardService;
        $this->states = [fn(Message $msg) => $this->state0($msg), fn(Message $msg) => $this->state1($msg), fn(Message $msg) => $this->state2($msg), fn(Message $msg) => $this->state3($msg), fn(Message $msg) => $this->state4($msg), fn(Message $msg) => $this->state5($msg), fn(Message $msg) => $this->state6($msg), fn(Message $msg) => $this->state7($msg)];
    }

    /**
     * @param IBotApi $botApi
     */
    public function setBotApi(IBotApi $botApi): void
    {
        $this->botApi = $botApi;
    }

    public function handle_message(Request $request): void
    {
        $msg = $this->botApi->get_message($request);
        $i = $msg->getUser()->state ?  : 0;
        $this->states[$i]($msg);
    }

    private function state0(Message $msg): void
    {
        $user = $msg->getUser();
        switch ($msg->getMessage()){
            case __('messages.add_card.option_name'):
                $user->update(['state' => 1]);
                $text = __('messages.add_card.number_prompt');
                $keyboard = [[new BotButton(__('messages.cancel'), 'negative')]];
                break;
            case __('messages.edit_card.option_name'):
                $user->update(['state' => 3]);
                $text = __('messages.edit_card.choose_prompt');
                $cards = $user->cards()->get();
                foreach ($cards as $card) {
                    $keyboard[] = [new BotButton($card->id.': '.$card->name.' ('.$card->number.')')];
                }
                $keyboard[] = [new BotButton(__('messages.cancel'), 'negative')];
                break;
            case __('messages.delete_card.option_name'):
                $user->update(['state' => 7]);
                $text = __('messages.delete_card.choose_prompt');
                $cards = $user->cards()->get();
                foreach ($cards as $card) {
                    $keyboard[] = [new BotButton($card->id.': '.$card->name.' ('.$card->number.')')];
                }
                $keyboard[] = [new BotButton(__('messages.cancel'), 'negative')];
                break;
            default:
                $name = $this->botApi->get_user_name($user);
                $cards = $user->cards()->get();
                if(count($cards) > 0){
                    $text = __('messages.get_balance.balance', ['name' => $name]);
                    foreach ($cards as $card){
                        $text .= $card->name." (".$card->number.") — ".$this->cardService->get_balance($card)."₽\n";
                    }
                } else{
                    $text = __('messages.get_balance.no_cards');
                }
                $keyboard = [[new BotButton(__('messages.get_balance.option_name'), 'primary')], [new BotButton(__('messages.add_card.option_name'))], [new BotButton(__('messages.edit_card.option_name')), new BotButton(__('messages.delete_card.option_name'), 'negative')]];
                break;
        }
        $this->botApi->send_message($user, $text, $keyboard);
    }

    private function state1(Message $msg): void
    {
        $user = $msg->getUser();
        switch ($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            default:
                $number = $msg->getMessage();
                $text = __('messages.add_card.name_prompt', ['number' => $number]);
                $keyboard = [[new BotButton(__('messages.cancel'), 'negative')]];
                $user->update(['state' => 2, 'data' => json_encode(['number' => $number])]);
                $this->botApi->send_message($user, $text, $keyboard);
                break;
        }
    }

    private function state2(Message $msg): void
    {
        $user = $msg->getUser();
        switch ($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                break;
            default:
                $name = $msg->getMessage();
                $number = json_decode($user->data)->number;
                try{
                    $this->cardService->create($user, ['number' => $number, 'name' => $name]);
                    $text = __('messages.add_card.success', ['number' => $number, 'name' => $name]);
                } catch (\Exception $exception){
                    $text = $exception->getMessage();
                }
                $user->update(['state' => 0]);
                $this->botApi->send_message($user, $text);
                break;
        }
        $this->state0($msg);
    }

    private function state3(Message $msg): void
    {
        $user = $msg->getUser();
        switch($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            default:
                $id = explode(':', $msg->getMessage())[0];
                $text = __('messages.edit_card.change_type_prompt');
                $keyboard = [[new BotButton(__('messages.edit_card.name')), new BotButton(__('messages.edit_card.number'))], [new BotButton(__('messages.cancel'), 'negative')]];
                $user->update(['state' => 4, 'data' => json_encode(['card_id' => $id])]);
                $this->botApi->send_message($user, $text, $keyboard);
                break;
        }
    }

    private function state4(Message $msg){
        $user = $msg->getUser();
        switch($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            case __('messages.edit_card.name'):
                $id = json_decode($user->data)->card_id;
                $card = $this->cardService->find($id);
                $text = __('messages.edit_card.name_prompt', ['number' => $card->number]);
                $keyboard = [[new BotButton(__('messages.cancel'), 'negative')]];
                $user->update(['state' => 5]);
                $this->botApi->send_message($user, $text, $keyboard);
                break;
            case __('messages.edit_card.number'):
                $id = json_decode($user->data)->card_id;
                $card = $this->cardService->find($id);
                $text = __('messages.edit_card.number_prompt', ['name' => $card->name]);
                $keyboard = [[new BotButton(__('messages.cancel'), 'negative')]];
                $user->update(['state' => 6]);
                $this->botApi->send_message($user, $text, $keyboard);
                break;
            default:
                $text = __('messages.edit_card.invalid_type');
                $keyboard = [[new BotButton(__('messages.edit_card.name')), new BotButton(__('messages.edit_card.number'))], [new BotButton(__('messages.cancel'), 'negative')]];
                $this->botApi->send_message($user, $text, $keyboard);
                break;
        }
    }

    private function state5(Message $msg){
        $user = $msg->getUser();
        switch($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            default:
                $name = $msg->getMessage();
                $id = json_decode($user->data)->card_id;
                $card = $this->cardService->find($id);
                try{
                    $this->cardService->update($card, ['number' => $card->number, 'name' => $name]);
                    $text = __('messages.edit_card.name_success', ['number' => $card->number, 'name' => $name]);
                } catch (ValidationException $exception){
                    $text = $exception->getMessage();
                }
                $user->update(['state' => 0]);
                $this->botApi->send_message($user, $text);
                $this->state0($msg);
                break;
        }
    }

    private function state6(Message $msg){
        $user = $msg->getUser();
        switch($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            default:
                $new_number = $msg->getMessage();
                $id = json_decode($user->data)->card_id;
                $card = $this->cardService->find($id);
                try{
                    $this->cardService->update($card, ['number' => $new_number, 'name' => $card->name]);
                    $text = __('messages.edit_card.number_success', ['number' => $new_number, 'name' => $card->name]);
                } catch (ValidationException $exception){
                    $text = $exception->getMessage();
                }
                $user->update(['state' => 0]);
                $this->botApi->send_message($user, $text);
                $this->state0($msg);
                break;
        }
    }

    private function state7(Message $msg){
        $user = $msg->getUser();
        switch($msg->getMessage()){
            case __('messages.cancel'):
                $user->update(['state' => 0]);
                $this->state0($msg);
                break;
            default:
                $id = explode(':', $msg->getMessage())[0];
                $card = $this->cardService->find($id);
                $text = __('messages.delete_card.success', ['number' => $card->number]);
                $this->cardService->delete($card);
                $user->update(['state' => 0]);
                $this->botApi->send_message($user, $text);
                $this->state0($msg);
                break;
        }
    }
}
