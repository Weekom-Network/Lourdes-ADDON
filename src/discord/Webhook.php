<?php

declare(strict_types = 1);

namespace discord;

use discord\task\DiscordWebhookSendTask;
use pocketmine\Server;

/**
 * Class Webhook
 * @package discord
 */
class Webhook
{
    /** @var string */
    protected $url;

    /**
     * Webhook constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param Message $message
     */
    public function send(Message $message): void
    {
        Server::getInstance()->getAsyncPool()->submitTask(new DiscordWebhookSendTask($this, $message));
    }
}