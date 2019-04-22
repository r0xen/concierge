<?php declare (strict_types = 1);

namespace Concierge\Commands\Job;

/**
 * TODO extends InstagramSendAbstract?
 */
class InstagramSendText extends JobAbstract
{
    /**
     * Our text
     *
     * @var string
     */
    public $text;
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
    public function __construct(string $text, string $recipient, string $client)
    {
        $this->text = $text;
        $this->recipient = $recipient;
        $this->client = $client;
    }


    public function getClient(): string
    {
        return $this->client;
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

     /**
     * returns text
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

}
