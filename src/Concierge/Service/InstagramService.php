<?php

namespace Concierge\Service;

use Closure;
use InstagramAPI\Push;
use Concierge\Concierge;
use InstagramAPI\Instagram;
use React\EventLoop\LoopInterface;
use Concierge\Commands\NullCommand;
use InstagramAPI\Push\Notification;
use Concierge\Service\Handler\HandlerDirect;
use Concierge\Service\Handler\HandlerComment;
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

    /**
     * Mediator
     *
     * @var Concierge
     */
    private $concierge;

    /**
     * Construcot
     *
     * @param Concierge $concierge
     * @param string $id
     * @param Instagram $instagram
     * @param LoopInterface $loop
     */
    public function __construct(Concierge $concierge, string $id, Instagram $instagram, LoopInterface $loop)
    {
        $this->concierge = $concierge;
        $this->id = $id;
        $this->instagram = $instagram;
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
     * @return Instagram
     */
    private function getInstagram(): Instagram
    {
        return $this->instagram;
    }

    public function sendMessage(string $text, string $recipient)
    {
        $recipient = [
            'users' => [$this->getInstagram()->people->getUserIdForName($recipient)]
        ];
        $this->getInstagram()->direct->sendText($recipient, $text);
    }

    public function sendComment(string $text, string $mediaId, string $replyCommentId){
        $this->getInstagram()->media->comment($mediaId, $text, $replyCommentId);
    }

    /**
     * Handle Notifications
     * 
     * @param Notification $push
     * @return CommandInterface
     */
    private function handlePush(Notification $push): CommandInterface
    {
        // todo use dependency manager instead of this crap
        // questi handler vanno instanziati una volta sola, potrebbero anche essere classi statiche.

        switch ($push->getCollapseKey()) {
            case 'direct_v2_message':
                $handler =  new HandlerDirect($this->id, $this->getInstagram(), $push);
                break;
            case 'comment':
            case 'reply_to_comment_with_threading':
                $handler = new HandlerComment($this->id, $this->getInstagram(), $push);
                break;
            default:
                return new NullCommand();
        }

        return $handler->retrieveCommand();
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

        $this->getPushService()->on('comment', Closure::fromCallable([$this, 'orchestrate']));
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
