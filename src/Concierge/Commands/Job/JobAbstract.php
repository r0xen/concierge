<?php

namespace Concierge\Commands\Job;

use Concierge\Commands\CommandInterface;

/**
 * Abstract class for jobs
 */
abstract class JobAbstract implements CommandInterface
{

    abstract public function getRecipient(): string;
    abstract public function getText(): string;

}
