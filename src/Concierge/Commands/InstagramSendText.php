<?php

namespace Concierge\Commands;

/**
 * TODO extends InstagramSendAbstract?
 */
class InstagramSendText extends JobAbstract
{
    /**
     * Our answer
     *
     * @var string
     */
    public $answer;
    /**
     * Recipient name
     *
     * @var string
     */
    public $recipient;
    /**
     * Instagram client to use
     *
     * @var string
     */
    public $client;

    /**
     * Constructor
     *
     * @param string $answer
     * @param string $recipient
     * @param string $client
     */
    public function __construct(string $answer, string $recipient, string $client)
    {
        $this->answer = $answer;
        $this->recipient = $recipient;
        $this->client = $client;
    }

    /**
     * Returns recipient
     *
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }
}
