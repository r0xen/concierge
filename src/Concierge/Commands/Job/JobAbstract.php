<?php

namespace Concierge\Commands\Job;

use Concierge\Commands\CommandInterface;

/**
 * Abstract class for jobs
 */
abstract class JobAbstract implements CommandInterface
{

    abstract public function getClient(): string;
    abstract public function getText(): string;

}
