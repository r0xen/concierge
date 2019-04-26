<?php declare (strict_types = 1);

namespace Concierge\Models;


class ModelAbstract
{
    private $from;
    private $to;
    private $text;


    public function __construct(string $from, string $to, string $text)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
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
}
