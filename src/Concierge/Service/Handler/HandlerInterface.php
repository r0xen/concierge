<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use Concierge\Commands\CommandInterface;

interface HandlerInterface
{
    public function retrieveCommand(): CommandInterface;
}
