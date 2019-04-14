<?php

namespace Concierge;

use InstagramAPI\Instagram;
use React\EventLoop\Factory;
use unreal4u\TelegramAPI\TgLog;
use React\EventLoop\LoopInterface;
use Concierge\Commands\JobAbstract;
use Concierge\Service\TelegramService;
use Concierge\Service\InstagramService;
use Concierge\Commands\CommandInterface;
use unreal4u\TelegramAPI\HttpClientRequestHandler;

/**
 * TODO: 
 * 1) tirare fuori instagram / logger
 * 2) aggiungere altri comandi (send image/ audio/video via tg)
 */
class Concierge
{

    /**
     * Instance to Instagram Service
     *
     * @var InstagramService 
     */
    private $instagram;

    /**
     * Instance to Telegram Service
     *
     * @var TelegramService
     */
    private $telegram;

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
     * Constructor function
     */
    public function __construct()
    {
        // $this->_logger = $logger;
        $this->loop = Factory::create();
        $this->instagram[IG_USERNAME] = $this->setupInstagram($this->loop);
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
        $this->instagram[$id] = new InstagramService($id, $ig, $this->loop);
    }
    /**
     * Returns Telegram Service
     *
     * @return TelegramService
     */
    public function getTelegram(): TelegramService
    {
        return $this->telegram;
    }

    /**
     * Returns Instagram Service
     *
     * @return InstagramService
     */
    public function getInstagram(string $id): InstagramService
    {
        return $this->instagram[$id];
    }

    /**
     * Creates the Instagram Service
     *
     * @param LoopInterface $loop
     * @return InstagramService
     */
    private function setupInstagram(LoopInterface $loop): InstagramService
    {
        $ig = new \InstagramAPI\Instagram(false, false);
        try {
            $loginResponse = $ig->login(IG_USERNAME, IG_PASSWORD);
            if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
                $verificationCode = trim(fgets(STDIN));
                $ig->finishTwoFactorLogin(IG_USERNAME, IG_PASSWORD, $twoFactorIdentifier, $verificationCode);
            }
        } catch (\Exception $e) {
            echo 'Something went wrong: ' . $e->getMessage() . "\n";
            exit(0);
        }
        return new InstagramService($this, IG_USERNAME, $ig, $loop);
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

    public function notify($service){
        if($service instanceof InstagramService){
            while (!$service->jobsForTelegram->isEmpty()) {
                $job = $service->jobsForTelegram->dequeue();
                $this->getTelegram()->sendMessage($job->getText(), $job->getRecipient());
                echo "\nmessaggio su telegram inviato\n";
            }
        }
        else{
            while (!$service->jobsForInstagram->isEmpty()) {
                $job = $service->jobsForInstagram->dequeue();
                $job->recipient = [
                    'users' => [$this->getInstagram($job->client)->getInstagram()->people->getUserIdForName($job->recipient)]
                ];
                $this->getInstagram($job->client)->getInstagram()->direct->sendText($job->recipient, $job->answer);
                echo "\n inviato dm\n";
            }
        }
    }

    /**
     * Runs the services and performs his duty as Concierge!
     *
     * @return void
     */
    public function run()
    {
        /** @var TelegramService $concierge */
        $concierge = $this->getTelegram();

        foreach ($this->instagram as $instagramInstance) {
            $instagramInstance->startService();
        }
        
        $concierge->startService();

        $this->loop->run();
    }
}
