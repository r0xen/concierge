<?php

namespace Concierge\Service;

interface ServiceInterface
{
    public function startService();
    public function stopService();
    public function sendMessage(string $text, string $recipient);
}
