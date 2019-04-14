<?php

namespace Concierge\Service;

use Closure;
use SplQueue;
use InstagramAPI\Push;
use Concierge\Concierge;
use InstagramAPI\Instagram;
use React\EventLoop\LoopInterface;
use Concierge\Commands\HandlerPush;
use Concierge\Commands\NullCommand;
use InstagramAPI\Push\Notification;
use Concierge\Commands\CommandInterface;

/**
 * Classe Instagram Service
 */
class InstagramService implements ServiceInterface
{
    /**
     * Instance to Instagram API
     *
     * @var InstagramAPI\Instagram
     */
    private $instagram;
    /**
     * Instance to EventLoop
     *
     * @var LoopInterface
     */
    private $loop;
    /**
     * Instance to PushService
     *
     * @var Push
     */
    private $pushService;

    private $concierge;

    /**
     * Constructor
     *
     * @param string $id
     * @param Instagram $instagram
     * @param LoopInterface $loop
     */
    public function __construct(Concierge $concierge, string $id, Instagram $instagram, LoopInterface $loop)
    {
        $this->concierge = $concierge;
        $this->instagram = $instagram;
        $this->id = $id;
        $this->loop = $loop;
        $this->pushService = new Push($this->loop, $this->instagram);
    }

    /**
     * returns PushService
     *
     * @return Push
     */
    private function getPushService(): Push
    {
        return $this->pushService;
    }

    /**
     * Returns Instagram API istance
     *
     * @return 
     */
    public function getInstagram()
    {
        return $this->instagram;
    }

    public function sendMessage(string $text, string $recipient){
        $recipient = [
            'users' => [$this->getInstagram()->people->getUserIdForName($recipient)]
        ];
        $this->getInstagram()->direct->sendText($recipient, $text);
    }

    /**
     * Handle Notifications
     * 
     * @param Notification $push
     * @return CommandInterface
     */
    private function handlePush(Notification $push): CommandInterface
    {
        // todo per ogni push ttype itera su tutte le factory vedendo se la becca
        // var_dump($push);

        switch($push->getCollapseKey()){
            case 'direct_v2_message':
                $obj =  new HandlerPush($this->id, $this->getInstagram(), $push);
                $obj = $obj->parseDirectPush();
                return $obj;
            default:
                return new NullCommand();
                break; 
        }
    
    }

    /**
     * Orchestrates the whole thing
     *
     * @param Notification $push
     * @return void
     */
    private function orchestrate(Notification $push): void
    {
        $command = $this->handlePush($push);
        if (!$command instanceof NullCommand) {
            $this->concierge->notify($this, $command);
        }
    }

    /**
     * Add new command to the queue
     *
     * @param CommandInterface $job
     * @return void
     */
    private function addJob(CommandInterface $job): void
    {
        $this->jobsForTelegram->enqueue($job);
    }

    /**
     * Starts the Push Notification Service
     *
     * @return void
     */
    public function startService(): void
    {
        $this->getPushService()->start();

        $this->getPushService()->on('direct_v2_message', Closure::fromCallable([$this, 'orchestrate']));
        /** log? */
        // $this->getPushService()->on('incoming', Closure::fromCallable([$this, 'orchestrate']));

    }

    /**
     * Stops the Push Notification Service
     *
     * @return void
     */
    public function stopService(): void
    {
        $this->getPushService()->stop();
    }
}
