<?php

namespace Concierge\Commands\Job;

class TelegramSendText extends JobAbstract
{
    /**
     * Text to send
     *
     * @var string
     */
    private $text;
    /**
     * Chat id to sends message to
     *
     * @var string
     */
    private $recipient;

    /**
     * Constructor
     *
     * @param string $text
     * @param string $chatID
     */
    public function __construct(string $text, string $chatID)
    {
        $this->text = $text;
        $this->recipient = $chatID;
    }

    /**
     * returns text
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * returns recipient
     *
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getClient(): string
    {
        return '';
    }
}
