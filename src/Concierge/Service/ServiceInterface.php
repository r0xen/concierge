<?php

namespace Concierge\Service;

/**
 * Interface implements by all services
 */
interface ServiceInterface
{
    public function startService();
    public function stopService();
    public function sendMessage(string $text, string $recipient);
}
