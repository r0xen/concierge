<?php declare (strict_types = 1);

namespace Concierge\Commands\Job;

/**
 * TODO extends InstagramSendAbstract?
 */
class InstagramGetPending extends JobAbstract
{
    private $client;

    public function __construct(string $client)
    {
        $this->client = $client;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getText(): string
    {
        return '';
    }
}
