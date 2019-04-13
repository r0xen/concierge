<?php

namespace Concierge\Commands;

/**
 * Commands are task that can and must be executed by the services
 */
abstract class CommandAbstract implements CommandInterface
{
    abstract public function execute(): void;
}
