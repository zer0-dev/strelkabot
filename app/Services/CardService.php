<?php

namespace App\Services;

use App\Models\Card;
use App\Models\User;
use App\Rules\StrelkaNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CardService{
    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function create(User $user, array $values){
        $validated = Validator::make($values, ['number' => ['required','regex:/^[0-9]+$/','size:11', new StrelkaNumber], 'name' => 'required|string|max:255'])->validate();
        if($user->cards()->count() < 5){
            $card = $user->cards()->create($validated);
            return $card;
        } else{
            throw new \Exception('Maximum 5 cards per user. Remove other cards');
        }
    }

    /**
     * @throws ValidationException
     */
    public function update(Card $card, array $values){
        $validated = Validator::make($values, ['number' => ['required','regex:/^[0-9]+$/','size:11', new StrelkaNumber], 'name' => 'required|string|max:255'])->validate();
        $card->update($validated);
        return $card;
    }

    public function delete(Card $card){
        return $card->delete();
    }

    public function list(): Collection
    {
        return Card::all();
    }

    public function find($id){
        return Card::findOrFail($id);
    }

    /**
     * @throws \Exception
     */
    public function get_balance(Card $card): float{
        $response = Http::withHeaders(['Referer' => 'https://strelkacard.ru/'])->get('https://strelkacard.ru/api/cards/status/?cardnum='.$card->number.'&cardtypeid=3ae427a1-0f17-4524-acb1-a3f50090a8f3');
        if(!empty($response->json()['balance'])){
            return $response->json()['balance']/100;
        } else {
            throw new \Exception('Card number is invalid');
        }
    }
}
