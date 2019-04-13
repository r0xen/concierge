<?php

namespace Concierge\Service;

use Closure;
use SplQueue;
use InstagramAPI\Push;
use InstagramAPI\Instagram;
use React\EventLoop\LoopInterface;
use Concierge\Commands\NullCommand;
use InstagramAPI\Push\Notification;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\TelegramSendText;
use InstagramAPI\Response\Model\DirectThread;
use InstagramAPI\Response\Model\DirectThreadItem;

/**
 * Classe Instagram Service
 */
class InstagramService
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
     * Queue of jobs for telegram
     *
     * @var Queue
     */
    public $jobsForTelegram;

    /**
     * Constructor
     *
     * @param string $id
     * @param Instagram $instagram
     * @param LoopInterface $loop
     */
    public function __construct(string $id, Instagram $instagram, LoopInterface $loop)
    {
        $this->instagram = $instagram;
        $this->id = $id;
        $this->loop = $loop;
        $this->pushService = new Push($this->loop, $this->instagram);
        $this->jobsForTelegram = new SplQueue();
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
    public function getInstagram(): Instagram
    {
        return $this->instagram;
    }

    /**
     * Helper function 
     *
     * @param string $id
     * @return DirectThread
     */
    private function getThread(string $id): DirectThread
    {
        return $this->getInstagram()->direct->getThread($id)->getThread();
    }

    /**
     * Handle RavenMedia items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleRavenMedia(DirectThreadItem $item): string
    {
        $item = $item->getVisualMedia()->getMedia();
        switch ($item->getMediaType()) {
            case 1:
                $item = $item->getImageVersions2()['candidates'];
                break;
            case 2:
                $item = $item->getVideoVersions();
                break;
        }
        return $item[0]['url'];
    }

    /**
     * Handle Media items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleMedia(DirectThreadItem $item): string
    {
        if ($item->getMedia()->isVideoVersions()) {
            $item = $item->getMedia()->getVideoVersions();
        } else {
            $item = $item->getMedia()->getImageVersions2()->getCandidates();
        }
        return $item[0]->getUrl();
    }
    /**
     * Handle Audio items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleAudio(DirectThreadItem $item): string
    {
        return $item->getVoiceMedia()['media']['audio']['audio_src'];
    }
    /**
     * HandleReelShare
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleReelShare(DirectThreadItem $item): string
    {
        return $item->getReelShare()->getText();
    }

    /**
     * Handle text items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleText(DirectThreadItem $item): string
    {
        return $item->getText();
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
        if ($push->getActionParam('id') && $push->getActionParam('x')) {
            $thread = $this->getThread($push->getActionParam('id'));
            $text = "[$this->id] @" . $thread->getUsers()[0]->getUsername() . ": ";

            foreach ($thread->getItems() as $item) {
                if ($item->getItemId() == $push->getActionParam('x')) {
                    switch ($item->getItemType()) {
                        case 'text':
                            $text .= $this->handleText($item);
                            break;
                        case 'raven_media':
                            $text .= $this->handleRavenMedia($item);
                            break;
                        case 'media':
                            $text .= $this->handleMedia($item);
                            break;
                        case 'voice_media':
                            $text .= $this->handleAudio($item);
                            break;
                        case 'reel_share':
                            $text .= $this->handleReelShare($item); // story reply
                            break;
                        default:
                            break;
                    }
                }
            }
            return new TelegramSendText($text, A_USER_CHAT_ID);
        }
        return new NullCommand();
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
            $this->addJob($command);
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
