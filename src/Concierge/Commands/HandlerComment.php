<?php

namespace Concierge\Commands;

use InstagramAPI\Push\Notification;
use Concierge\Commands\Job\TelegramSendText;
use InstagramAPI\Instagram;
/**
*
 *   Comments:
 *     - comment - "USERNAME commented: "TEXT""
 *       media?id=1111111111111111111_1111111111&forced_preview_comment_id=11111111111111111
 *       comments_v2?media_id=1111111111111111111_1111111111&target_comment_id=11111111111111111
 *     - mentioned_comment - "USERNAME mentioned you in a comment: TEXT..."
 *       media?id=1111111111111111111_1111111111
 *       comments_v2?media_id=1111111111111111111_1111111111
 *     - comment_on_tag - "USERNAME commented on a post you're tagged in."
 *       media?id=1111111111111111111 <- Yep, no author ID here.
 *     - comment_subscribed - "USERNAME also commented on USERNAME's post: "TEXT""
 *       comments_v2?media_id=1111111111111111111_1111111111&target_comment_id=11111111111111111
 *     - comment_subscribed_on_like - "USERNAME commented on a post you liked: TEXT"
 *       comments_v2?media_id=1111111111111111111_1111111111&target_comment_id=11111111111111111
 *     - reply_to_comment_with_threading - "USERNAME replied to your comment on your post: "TEXT""
 *       comments_v2?media_id=1111111111111111111_1111111111&target_comment_id=11111111111111111
 */
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