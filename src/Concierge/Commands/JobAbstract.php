<?php

namespace Concierge\Commands;

/**
 * Abstract class for jobs
 */
abstract class JobAbstract implements CommandInterface
{

    abstract public function getRecipient();
}