<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Instagram;
use Concierge\Models\Comment;
use InstagramAPI\Push\Notification;

class HandlerComment implements HandlerInterface
{

    /**
     * Parse a comment push notif
     *
     * @return Comment
     */
    public static function parsePush(string $client, Instagram $instagram, Notification $push): Comment
    {
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

        $lastComment = $instagram->media->getComments($mediaId, $commentId)->getComments();
        $username = $lastComment[0]->getUser()->getUsername();

        if (sizeof($lastComment[count($lastComment) - 1]->getPreviewChildComments()) > 0) {
            $lastComment = $lastComment[count($lastComment) - 1]->getPreviewChildComments();
            $lastComment = $lastComment[count($lastComment) - 1]->getText();
        } else {
            $lastComment = $lastComment[0]->getText();
        }

        return new Comment(
            $username,
            $client,
            $lastComment,
            $instagram->media->getPermalink($mediaId)->getPermalink(),
            $mediaId,
            $commentId['target_comment_id'][0]
        );
    }
}
