<?php

namespace Concierge\Commands;

use InstagramAPI\Push\Notification;
use Concierge\Commands\Job\TelegramSendText;
use InstagramAPI\Instagram;

class HandlerComment {

    private $instagram;
    private $push;

    public function __construct(string $client, Instagram $instagram, Notification $push)
    {
        $this->client = $client;
        $this->instagram = $instagram;
        $this->push = $push;        
    }

    public function parsePush(){
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
        $lastComment = $lastComment[sizeof($lastComment)-1];

        $text = "[$this->client] @".$lastComment->getUser()->getUsername()." commented: \"<b>". $lastComment->getText()."\"</b>";
        $text .= " on your <a href=\"". $this->instagram->media->getPermalink($mediaId)->getPermalink()."\">post</a>";
        
        return new TelegramSendText($text, A_USER_CHAT_ID);


    }
}