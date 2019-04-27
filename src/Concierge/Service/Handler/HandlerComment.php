<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Comment;
use InstagramAPI\Push\Notification;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\Job\TelegramSendText;

class HandlerComment implements HandlerInterface
{

    /**
     * Instance to instagram service
     *
     * @var Instagram
     */
    private $instagram;
    /**
     * Push notif
     *
     * @var Notification
     */
    private $push;

    /**
     * Constructor
     *
     * @param string $client
     * @param Instagram $instagram
     * @param Notification $push
     */
    public function __construct(string $client, Instagram $instagram, Notification $push)
    {
        $this->client = $client;
        $this->instagram = $instagram;
        $this->push = $push;
    }
    /**
     * Parse a comment push notif
     *
     * @return Comment
     */
    private function parsePush(): Comment
    {
        $push = $this->push;
        switch ($push->getActionPath()) {
            case 'comments_v2':
                $mediaId = $push->getActionParam('media_id');
                $commentId = $push->getActionParam('target_comment_id');
                break;
            case 'media':
            default:
                $mediaId = $push->getActionParam('id');
                $commentId = $push->getActionParam('forced_preview_comment_id');
        }
        $commentId = [
            'target_comment_id' => [$commentId]
        ];

        $lastComment = $this->instagram->media->getComments($mediaId, $commentId)->getComments();
        $username = $lastComment[0]->getUser()->getUsername();

        if (sizeof($lastComment[count($lastComment) - 1]->getPreviewChildComments()) > 0) {
            $lastComment = $lastComment[count($lastComment) - 1]->getPreviewChildComments();
            $lastComment = $lastComment[count($lastComment) - 1]->getText();
        } else {
            $lastComment = $lastComment[0]->getText();
        }

        return new Comment(
            $username,
            $this->client,
            $lastComment,
            $this->instagram->media->getPermalink($mediaId)->getPermalink(),
            $mediaId,
            $commentId['target_comment_id'][0]
        );
    }

    public static function createJob(Comment $comment)
    {
        $text = sprintf('<i>[%s]</i> @%s commented: "%s" on your <a href="%s#%s#%s">post</a>', $comment->getClient(), $comment->getFrom(), $comment->getText(), $comment->getPost(), $comment->getMediaId(), $comment->getCommentId());
        return new TelegramSendText($text, A_USER_CHAT_ID);
    }

    /**
     * returns job to do
     *
     * @return CommandInterface
     */
    public function retrieveCommand(): CommandInterface
    {
        $comment = $this->parsePush();
        return self::createJob($comment);
    }
}
