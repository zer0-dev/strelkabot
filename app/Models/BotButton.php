<?php

namespace App\Models;

class BotButton
{
    private string $text, $color;

    public function __construct(string $text, string $color = 'secondary'){
        $this->text = $text;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }
}
