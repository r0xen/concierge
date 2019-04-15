<?php

namespace Concierge\Service;

use Concierge\Concierge;
use Concierge\Service\Handler\HandlerMessage;
use unreal4u\TelegramAPI\TgLog;
use React\EventLoop\LoopInterface;
use Concierge\Commands\Job\JobAbstract;
use React\Promise\PromiseInterface;
use Concierge\Commands\CommandInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Custom\UpdatesArray;

class TelegramService implements ServiceInterface
{
    /**
     * Telegram API instance
     *
     * @var TgLog
     */
    private $tgLog;

    private $concierge;
    /**
     * ID last update fetched
     *
     * @var int
     */
    private $lastUpdate;
    /**
     * EventLoop
     *
     * @var LoopInterface
     */
    private $loop;
    /**
     * Commands Factory instance
     *
     * @var Factory
     */
    private $factory;

    /**
     * Constructor
     *
     * @param TgLog $telegram
     * @param LoopInterface $loop
     */
    public function __construct(Concierge $concierge, TgLog $telegram, LoopInterface $loop)
    {
        $this->concierge = $concierge;
        $this->tgLog = $telegram;
        $this->loop = $loop;
        $this->lastUpdate = 0;
        $this->factory = new HandlerMessage($this); // todo e' orrendo
    }
    /**
     * Sends a message
     *
     * @param string $text
     * @param string $chatID
     * @return PromiseInterface
     */
    public function sendMessage(string $text, string $chatID): PromiseInterface
    {
        $message = new SendMessage();
        $message->chat_id = $chatID;
        $message->text = $text;
        $message->parse_mode = 'HTML';

        return $this->tgLog->performApiRequest($message);
    }

    /**
     * Request Updates
     *
     * @return PromiseInterface
     */
    private function getUpdates(): PromiseInterface
    {
        $getUpdates = new GetUpdates();
        $getUpdates->timeout = 900;
        $getUpdates->offset = $this->lastUpdate + 1;
        $getUpdates->allowed_updates = ["message"];

        return $this->tgLog->performApiRequest($getUpdates);
    }
    /**
     * Check if owner chat
     *
     * @param Message $message
     * @return boolean
     */
    private function authenticate(Message $message): bool
    {
        if ($message->chat->id == A_USER_CHAT_ID) {
            return true;
        }
        return false;
    }

    /**
     * Returns a Command or a Job
     * 
     *
     * @param Message $message
     * @return CommandInterface
     */
    private function handleMessage(Message $message): CommandInterface
    {
        return $this->factory->createCommandFromMessage($message);
    }
    /**
     * Long polling async autoupdates
     *
     * @param PromiseInterface $updatePromise
     * @return void
     */
    private function autoUpdate(PromiseInterface $updatePromise)
    {
        $this->loop->addTimer(1, function () use (&$updatePromise) {
            $updatePromise->then(
                function (UpdatesArray $updatesArray) use (&$updatePromise) {
                    printf("[+] Called fetch updatePromise.then() \n");
                    /** @var Update $update */
                    foreach ($updatesArray as $update) {
                        if ($update->update_id <= $this->lastUpdate)
                            continue;
                        $this->lastUpdate = $update->update_id;
                        $this->handleUpdates($update);
                    }
                    $updatePromise = $this->getUpdates();
                    $this->autoUpdate($updatePromise);
                },
                function (\Exception $exception) {
                    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
                }
            );
        });
    }

    /**
     * Starts the service
     *
     * @return void
     */
    public function startService()
    {
        $updatePromise = $this->getUpdates();
        $this->autoUpdate($updatePromise);
    }

    public function stopService()
    {
        
    }
    /**
     * Business logic for updates
     *
     * @param Update $update
     * @return void
     */
    private function handleUpdates(Update $update): void
    {
        if ($this->authenticate($update->message)) {
            $command = $this->handleMessage($update->message);
            if ($command instanceof JobAbstract) {
                $this->concierge->notify($this, $command);
                return;
            }
            $command->execute();
        }
    }
}
