<?php

namespace Concierge\Service\Handler;

use Concierge\Commands\CommandInterface;

interface HandlerInterface{
    public function retrieveCommand(): CommandInterface;
}