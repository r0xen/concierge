<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use Concierge\Commands\HelpCommand;
use Concierge\Commands\NullCommand;
use Concierge\Service\TelegramService;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\Job\InstagramSendText;
use Concierge\Commands\Job\InstagramGetPending;
use Concierge\Commands\Job\InstagramSendComment;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use Concierge\Commands\Job\InstagramGetChat;

class HandlerMessage implements HandlerInterface
{
    /**
     * Some Commands may be need it
     *
     * @var TelegramService
     */
    private $telegram;
    private $message;

    /**
     * Factory Constructor
     *
     * @param TelegramService $telegram
     */
    public function __construct(TelegramService $telegram, Message $message)
    {
        $this->telegram = $telegram;
        $this->message = $message;
    }

    /**
     * Returns the appropiate command according to the message
     *
     * @param Message $message
     * @see https://core.telegram.org/bots/api#message for more info
     * @return CommandInterface
     */
    public function parse(): CommandInterface
    {
        $message = $this->message;
        if (isset($message->reply_to_message)) {
            if (isset($message->text)) {
                $comment = strpos($message->reply_to_message->text, "commented");
                $semiColon = strpos($message->reply_to_message->text, ':');
                $client = $this->getClientFromMessage($message->reply_to_message->text);
                $recipient = $this->getUsernameFromMessage($message->reply_to_message->text);

                if ($comment !== 0 && $comment < $semiColon) {
                    foreach ($message->reply_to_message->entities as $entity) {
                        if ($entity->type === 'text_link') {
                            $match = explode('#', parse_url($entity->url)['fragment']);
                            $text = '@' . $recipient . " " . $message->text; // vincolo delle api
                            return new InstagramSendComment($client, $text, $match[0], $match[1]);
                        }
                    }
                }
                return $this->handleTextReply($message->reply_to_message, $message->text);
            }
        }

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

    public function retrieveCommand(): CommandInterface
    {
        return $this->parse();
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
            $answer = $answerText ?? str_replace('[' . $client . ']', '', str_replace('@' . $recipient, '', $message->text));
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
        $message->text .= " ";

        switch ($botCommand) {
            case 'help':
                return new HelpCommand($this->telegram);
            case 'pending':
                $client = $this->getClientFromMessage($message->text);
                return new InstagramGetPending($client);
            case 'dm':
                $client = $this->getClientFromMessage($message->text);
                $recipient = $this->getUsernameFromMessage($message->text);
                return new InstagramGetChat($client, $recipient);
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
    private function getClientFromMessage(string $text): ?string
    {
        $start = strpos($text, '[');
        $end = strpos($text, ']');
        if (($start && $end) == false) {
            return 'default';
        }
        return substr($text, $start + 1, $end - $start - 1);
    }

    /**
     * Helper to extract username from '@username:' or '@username '
     *
     * @param string $text
     * @return void
     */
    private function getUsernameFromMessage(string $text): ?string
    {
        $start = strpos($text, '@');
        $firstSemicolon = strpos($text, ':');
        $firstSpace = strpos($text, ' ', $start);
        $end = $firstSemicolon > $firstSpace ? $firstSpace : ($firstSemicolon == 0 ? $firstSpace : $firstSemicolon);

        return substr($text, $start + 1, $end - $start - 1);
    }
}
