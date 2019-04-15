<?php

namespace Concierge\Models;

class Comment
{

    private $from;
    private $to;
    private $text;
    private $post;

    public function __construct(string $from, string $to, string $text, string $post)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
        $this->post = $post;
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
}