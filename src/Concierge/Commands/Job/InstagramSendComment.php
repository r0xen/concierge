<?php declare (strict_types = 1);

namespace Concierge\Commands\Job;

/**
 * TODO extends InstagramSendAbstract?
 */
class InstagramSendComment extends JobAbstract
{
    private $client;
    private $text;
    private $mediaId;
    private $replyCommentId;

    public function __construct(string $client, string $text, string $mediaId, string $replyCommentId)
    {
        $this->client = $client;
        $this->text = $text;
        $this->mediaId = $mediaId;
        $this->replyCommentId = $replyCommentId;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getRecipient(): string
    {
        return '';
    }

    public function getMediaId()
    {
        return $this->mediaId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getReplyCommentId()
    {
        return $this->replyCommentId;
    }
}
