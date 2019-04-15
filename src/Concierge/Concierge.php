<?php

namespace Concierge;

use InstagramAPI\Instagram;
use React\EventLoop\Factory;
use unreal4u\TelegramAPI\TgLog;
use React\EventLoop\LoopInterface;
use Concierge\Commands\Job\JobAbstract;
use Concierge\Service\TelegramService;
use Concierge\Service\InstagramService;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use Concierge\Service\ServiceInterface;

/**
 * TODO: 
 * 1)  logger
 * 2) aggiungere altri comandi (send image/ audio/video via tg)
 */
class Concierge
{

    /**
     * Instance to Instagram Service
     *
     * @var InstagramService[] 
     */
    private $instagram;

    /**
     * Instance to Telegram Service
     *
     * @var TelegramService
     */
    private $telegram;

    private $lastClient;

    /**
     * Instance to a PSR-3 compatible logger
     *
     * @var Monolog\Logger
     */
    private $logger;

    /**
     * Instance to React's Loop
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * Constructor
     *
     * @param string $id
     * @param Instagram $ig
     */
    public function __construct(string $id, Instagram $ig)
    {
        // $this->_logger = $logger;
        $this->loop = Factory::create();
        $this->instagram[$id] = $this->setupInstagram($id, $ig, $this->loop);
        $this->lastClient = $id;
        $this->telegram = $this->setupTelegram($this->loop);
    }

    /**
     * Adds Instagram Service
     *
     * @param string $id
     * @param Instagram $ig
     * @return void
     */
    public function addInstagram(string $id, Instagram $ig)
    {
        $this->instagram[$id] = new InstagramService($this, $id, $ig, $this->loop);
    }

    /**
     * Returns Telegram Service
     *
     * @return TelegramService
     */
    private function getTelegram(): TelegramService
    {
        return $this->telegram;
    }

    /**
     * Returns Instagram Service
     *
     * @return InstagramService
     */
    private function getInstagram(string $id): InstagramService
    {
        // if client not found then use last used instagram client
        if (!array_key_exists($id, $this->instagram)) {
            $id = $this->lastClient;
        }
        $this->lastClient = $id;

        return $this->instagram[$id];
    }

    /**
     * setup instagram service
     *
     * @param string $id
     * @param Instagram $ig
     * @param LoopInterface $loop
     * @return InstagramService
     */
    private function setupInstagram(string $id, Instagram $ig, LoopInterface $loop): InstagramService
    {
        return new InstagramService($this, $id, $ig, $loop);
    }

    /**
     * Creates the Telegram Service
     *
     * @param LoopInterface $loop
     * @return Telegram
     */
    private function setupTelegram(LoopInterface $loop): TelegramService
    {
        return new TelegramService($this, new TgLog(BOT_TOKEN, new HttpClientRequestHandler($loop)), $loop);
    }

    /**
     * Get notified by services on new jobs
     *
     * @param ServiceInterface $service
     * @param JobAbstract $job
     * @return void
     */
    public function notify(ServiceInterface $service, JobAbstract $job)
    {
        if ($service instanceof InstagramService) {
            $this->getTelegram()->sendMessage($job->getText(), $job->getRecipient());
        } else {
            $this->getInstagram($job->client)->sendMessage($job->getText(), $job->getRecipient());
        }
    }

    /**
     * Runs the services and performs his duty as Concierge!
     *
     * @return void
     */
    public function run()
    {
        $concierge = $this->getTelegram();

        foreach ($this->instagram as $instagramInstance) {
            $instagramInstance->startService();
        }

        $concierge->startService();

        $this->loop->run();
    }
}
