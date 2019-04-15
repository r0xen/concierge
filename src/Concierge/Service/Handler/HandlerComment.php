<?php

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Comment;
use InstagramAPI\Push\Notification;
use Concierge\Commands\CommandInterface;
use Concierge\Commands\Job\TelegramSendText;

class HandlerComment implements HandlerInterface {

    private $instagram;
    private $push;

    public function __construct(string $client, Instagram $instagram, Notification $push)
    {
        $this->client = $client;
        $this->instagram = $instagram;
        $this->push = $push;        
    }

    public function parsePush(): Comment{
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
        // var_dump($lastComment); exit(); // fix mostra sempre primo messaggio
        $lastComment = $lastComment[count($lastComment)-1]->getPreviewChildComments();
        $lastComment = $lastComment[count($lastComment)-1]->getText();
        // var_dump($lastComment); exit(); // fix mostra sempre primo messaggio

        return new Comment($username, $this->client, $lastComment, $this->instagram->media->getPermalink($mediaId)->getPermalink(), $mediaId, $commentId['target_comment_id'][0]);
    }

    public function retrieveCommand(): CommandInterface
    {
        $comment = $this->parsePush();
        $text = sprintf('<i>[%s]</i> @%s commented: "%s" on your <a href="%s#%s#%s">post</a>', $comment->getClient(), $comment->getFrom(), $comment->getText(),$comment->getPost(), $comment->getMediaId(), $comment->getCommentId());

        return new TelegramSendText($text, A_USER_CHAT_ID);
        
    }
}