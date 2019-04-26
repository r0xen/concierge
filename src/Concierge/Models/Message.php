<?php declare (strict_types = 1);

namespace Concierge\Models;

class Message extends ModelAbstract
{
    private $from;
    private $to;
    private $text;
    private $type;
    private $quotedText;
    private $reply;

    public function __construct(string $from, string $to, string $text, string $type, string $quotedText = null, bool $reply = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
        $this->type = $type;
        $this->quotedText = $quotedText;
        $this->reply = $reply;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getClient()
    {
        return $this->to;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getQuotedText()
    {
        return $this->quotedText;
    }

    public function isReply()
    {
        return $this->reply;
    }
}
