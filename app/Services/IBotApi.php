<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

interface IBotApi
{
    public function get_message(Request $request): Message|null;
    public function send_message(User $user, string $text, array $keyboard = []): void;
    public function make_keyboard(array $buttons): string;
    public function get_type(): string;
    public function get_user_name(User $user): string;
}
