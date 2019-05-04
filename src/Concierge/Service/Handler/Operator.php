<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Direct;
use Concierge\Commands\Job\JobAbstract;
use Concierge\Commands\Job\InstagramGetChat;
use Concierge\Service\Handler\HandlerDirect;
use Concierge\Commands\Job\InstagramGetPending;
use Concierge\Commands\NullCommand;

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
     * @param JobAbstract $job
     * @return JobAbstract[]
     */
    public function handleCommand(JobAbstract $job)
    {
        if ($job instanceof InstagramGetPending) {
            return $this->getPendingMessages();
        }
        if ($job instanceof InstagramGetChat) {
            return $this->getMessages($job->getText());
        }
    }

    private function getMessages($user): array
    {
        $handler = new HandlerDirect();
        $jobs = array();
        $thread = $this->instagram->direct->getThreadByParticipants(array($this->instagram->people->getUserIdForName($user)))->getThread();
        if ($thread === null) {
            $jobs[] = new NullCommand();
            return $jobs;
        }

        $from = $thread->getUsers()[0]->getUsername();
        foreach ($thread->getItems() as $item) {
            $jobs[] = $handler->createJob(new Direct(
                $from,
                $this->client,
                $handler->handleItemType($item),
                $item->getItemType(),
                true
            ));
        }
        return array_reverse($jobs);
    }

    private function getPendingMessages(): array
    {
        $handler = new HandlerDirect();
        $jobs = array();
        $pendingInbox = $this->instagram->direct->getPendingInbox()->getInbox();
        foreach ($pendingInbox->getThreads() as $thread) {
            $from = $thread->getUsers()[0]->getUsername();
            foreach ($thread->getItems() as $pendingItem) { // just last message
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
