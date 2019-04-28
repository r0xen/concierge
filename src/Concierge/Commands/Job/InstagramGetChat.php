<?php declare (strict_types = 1);

namespace Concierge\Commands\Job;

/**
 * TODO extends InstagramSendAbstract?
 */
class InstagramGetChat extends JobAbstract
{
    private $client;
    private $recipient;

    public function __construct(string $client, string $to)
    {
        $this->client = $client;
        $this->recipient = $to;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getText(): string
    {
        return $this->recipient;
    }
}
