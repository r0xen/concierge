<?php

namespace Concierge\Models;

class Comment
{

    private $from;
    private $to;
    private $text;
    private $post;
    private $mediaId;
    private $commentId;

    public function __construct(string $from, string $to, string $text, string $post, string $mediaId, string $commentId)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
        $this->post = $post;
        $this->mediaId = $mediaId;
        $this->commentId = $commentId;
    }


    public function getFrom(){
        return $this->from;
    }

    public function getClient(){
        return $this->to;
    }

    public function getText(){
        return $this->text;
    }

    public function getPost(){
        return $this->post;
    }

    public function getMediaId(){
        return $this->mediaId;
    }

    public function getCommentId(){
        return $this->commentId;
    }
}