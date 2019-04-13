<?php

namespace Concierge\Commands;

use Concierge\Commands\HelpCommand;
use Concierge\Commands\NullCommand;
use Concierge\Service\TelegramService;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\InstagramSendText;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class Factory
{
    /**
     * Some Commands may be need it
     *
     * @var TelegramService
     */
    private $telegram;

    /**
     * Factory Constructor
     *
     * @param TelegramService $telegram
     */
    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Returns the appropiate command according to the message
     *
     * @param Message $message
     * @see https://core.telegram.org/bots/api#message for more info
     * @return CommandInterface
     */
    public function createCommandFromMessage(Message $message): CommandInterface
    {
        if ($message->reply_to_message instanceof Message) {
            if ($message->text !== null) {
                return $this->handleTextReply($message->reply_to_message, $message->text);
            }
        }
        // only two types of messages: reply, and normal ones
        /** @var MessageEntity $entity */
        foreach ($message->entities as $entity) {
            switch ($entity->type) {
                case 'bot_command':
                    return $this->handleBotCommandEntity($message, $entity);
                case 'mention':
                    return $this->handleMentionEntity($message);
                case 'default':
                    break;
            }
        }

        return new NullCommand();
    }

    /**
     * Handle telegram text replies
     *
     * @param Message $message
     * @param [type] $answerText
     * @return CommandInterface
     */
    private function handleTextReply(Message $message, $answerText = null): CommandInterface
    {
        $recipient = $this->getUsernameFromMessage($message->text);
        $client = $this->getClientFromMessage($message->text);

        if ($recipient !== false) {
            $answer = $answerText ?? str_replace('['. $client .']', '', str_replace('@' . $recipient, '', $message->text));
            return new InstagramSendText($answer, $recipient, $client);
        }
        return new NullCommand();
    }

    /**
     * Handles mention telegram messages
     *
     * @param Message $message
     * @return CommandInterface
     */
    private function handleMentionEntity(Message $message): CommandInterface
    {
        return $this->handleTextReply($message);
    }

    /**
     * Handles Bot commands
     *
     * @param Message $message
     * @param MessageEntity $entity
     * @return CommandInterface
     */
    private function handleBotCommandEntity(Message $message, MessageEntity $entity): CommandInterface
    {
        $botCommand = trim(substr($message->text, $entity->offset + 1, $entity->length));

        switch ($botCommand) {
            case 'help':
                return new HelpCommand($this->telegram);
            default:
                return new NullCommand;
        }
    }

    /**
     * Helper to extract instagram client from text
     *
     * @param string $text
     * @return string
     */
    private function getClientFromMessage(string $text): string
    {
        $start = strpos($text, '[');
        $end = strpos($text, ']');
        return substr($text, $start + 1, $end - $start - 1);
    }

    /**
     * Helper to extract username from '@username:' or '@username '
     *
     * @param string $text
     * @return void
     */
    private function getUsernameFromMessage(string $text): string
    {
        $start = strpos($text, '@');
        $firstSemicolon = strpos($text, ':');
        $firstSpace = strpos($text, ' ', $start);
        $end = $firstSemicolon > $firstSpace ? $firstSpace : ($firstSemicolon == 0 ? $firstSpace : $firstSemicolon);

        return substr($text, $start + 1, $end - $start - 1);
    }
}
