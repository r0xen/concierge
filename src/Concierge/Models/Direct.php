<?php

namespace Concierge\Models;

class Direct{
    private $from;
    private $to;
    private $text;
    private $type;
    private $pending;

    public function __construct(string $from, string $to, string $text, string $type, bool $pending = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
        $this->type = $type;
        $this->pending = $pending;
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

    public function getType(){
        return $this->type;
    }

    public function isPending(){
        return $this->pending;
    }

    public function setPending(bool $pending){
        $this->pending = $pending;
    }


}