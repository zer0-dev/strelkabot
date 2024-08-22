<?php

namespace App\Models;

class Message
{
    private string $type, $message;
    private User $user;

    public function __construct(string $type, User $user, string $message){
        $this->type = $type;
        $this->user = $user;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
