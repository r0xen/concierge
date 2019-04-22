<?php declare (strict_types = 1);

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
