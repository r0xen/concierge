<?php declare (strict_types = 1);

namespace Concierge\Commands;

use Concierge\Service\TelegramService;

class HelpCommand extends CommandAbstract
{
    /**
     * TelegramService instance needed to answer back.
     *
     * @var TelegramService
     */
    private $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function execute(): void
    {
        $this->telegram->sendMessage("Hello, I am Concierge! I am here to help you manage your Instagram", A_USER_CHAT_ID);
    }
}
