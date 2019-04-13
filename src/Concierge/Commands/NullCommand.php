<?php

namespace Concierge\Commands;

/**
 * Null command which does nothing!
 */
class NullCommand extends CommandAbstract
{ 
    public function execute(): void
    {
        return;
    }
}
