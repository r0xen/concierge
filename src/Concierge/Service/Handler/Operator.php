<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Direct;
use Concierge\Commands\Job\JobAbstract;
use Concierge\Service\Handler\HandlerDirect;

/**
 * operatore gestisce la creazione di job. prende notifiche (push o comandi da telegram).
 * gli handler parsano e ritornano oggetti e basta. operator trasforma gli oggetti model in job
 * e li ritorna al servizio che notifica il mediatore.
 */
class Operator
{
    private $client;
    private $instagram;

    public function __construct(string $client, Instagram $instagram)
    {
        $this->instagram = $instagram;
        $this->client = $client;
    }

    /**
     * Undocumented function
     *
     * @param JobAbstract $command
     * @return JobAbstract[]
     */
    public function handleCommand(JobAbstract $command): array
    {
        // se dmpendign
        $handler = new HandlerDirect();
        $jobs = array();
        $pendingInbox = $this->instagram->direct->getPendingInbox()->getInbox();
        foreach ($pendingInbox->getThreads() as $thread) {
            $from = $thread->getUsers()[0]->getUsername();
            foreach ($thread->getItems() as $pendingItem) {
                $jobs[] = $handler->createJob(new Direct(
                    $from,
                    $this->client,
                    $handler->handleItemType($pendingItem),
                    $pendingItem->getItemType(),
                    true
                ));
            }
        }
        return $jobs;
    }
}
