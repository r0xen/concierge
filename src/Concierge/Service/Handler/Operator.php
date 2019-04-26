<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Direct;
use Concierge\Commands\NullCommand;
use Concierge\Models\ModelAbstract;
use InstagramAPI\Push\Notification;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\Job\TelegramSendText;
use Concierge\Service\Handler\HandlerDirect;
use Concierge\Service\Handler\HandlerComment;

/**
 * operatore gestisce la creazione di job. prende notifiche (push o comandi da telegram).
 * gli handler parsano e ritornano oggetti e basta. operator trasforma gli oggetti model in job
 * e li ritorna al servizio che notifica il mediatore.
 */
class Operator
{

    public function __construct()
    { }
    // job speciali da telegram ad instagram ( /dm e altre robe simili)
    public function handleCommand(string $client, Instagram $instagram, CommandInterface $command)
    {
        // se dmpending
        $pendingDirect = array();
        $pendingInbox = $instagram->direct->getPendingInbox()->getInbox();
        foreach ($pendingInbox->getThreads() as $thread) {
            $from = $thread->getUsers()[0]->getUsername();
            foreach ($thread->getItems() as $pendingItem) {
                $pendingDirect[] = HandlerDirect::retrieveCommand(
                    new Direct(
                        $from,
                        $client,
                        HandlerDirect::handleItemType($pendingItem),
                        $pendingItem->getItemType(),
                        true
                    )
                );
            }
        }


    }
    // public function getPending()
    // {
    //     $handler = new HandlerDirect();
    //     $pendingInbox = $this->getInstagram()->direct->getPendingInbox()->getInbox();
    //     foreach ($pendingInbox->getThreads() as $thread) {
    //         $from = $thread->getUsers()[0]->getUsername();
    //         foreach ($thread->getItems() as $pendingItem) {
    //             $text = $handler->handleItemType($pendingItem);
    //         }
    //     }
    // }

    public function handlePush(string $client, Instagram $instagram, Notification $push): ModelAbstract
    {
        switch ($push->getCollapseKey()) {
            case 'direct_v2_message':
                return HandlerDirect::parsePush($client, $instagram, $push);
            case 'comment':
            case 'reply_to_comment_with_threading':
                return HandlerComment::parsePush($client, $instagram, $push);
            default:
                break;
        }
    }

    // eventualmente da rimettere dentro gli handler.
    /**
     * Returns the Job to do
     *
     * @return CommandInterface
     */
    public function directJob($direct): CommandInterface
    {
        if ($direct->isPending()) {
            $text = sprintf("<i>[%s] pending dm</i> @%s", $direct->getClient(), $direct->getFrom());
        } else {
            $text = sprintf("<i>[%s]</i> @%s", $direct->getClient(), $direct->getFrom());
        }

        if ($direct->getType() !== "text" && $direct->getType() !== "reel_share") {
            $text .= sprintf(' sent you a <a href="%s">media</a>', $direct->getText());
            return new TelegramSendText($text, A_USER_CHAT_ID);
        }

        $text .= ": " . $direct->getText();
        return new TelegramSendText($text, A_USER_CHAT_ID);
    }

    /**
     * returns job to do
     *
     * @return CommandInterface
     */
    public function commentJob($comment): CommandInterface
    {
        $text = sprintf('<i>[%s]</i> @%s commented: "%s" on your <a href="%s#%s#%s">post</a>', $comment->getClient(), $comment->getFrom(), $comment->getText(), $comment->getPost(), $comment->getMediaId(), $comment->getCommentId());

        return new TelegramSendText($text, A_USER_CHAT_ID);
    }
}
