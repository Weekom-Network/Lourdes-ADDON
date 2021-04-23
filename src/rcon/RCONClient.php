<?php

declare(strict_types = 1);

namespace rcon;

/**
 * Class RCONClient
 * @package rcon
 */
class RCONClient
{
    /** @var \ThreadedLogger */
    protected $logger;
    /** @var RCONClientThread */
    protected $thread;

    /**
     * RCONClient constructor.
     * @param string $host
     * @param string $password
     * @param \ThreadedLogger $logger
     * @param int $port
     * @param int $timeout
     */
    public function __construct(string $host, string $password, \ThreadedLogger $logger, int $port = 19132, int $timeout = 2)
    {
        $this->logger = $logger;
        $this->thread = new RCONClientThread($host, $password, $logger, $port, $timeout);
    }

    public function stop()
    {
        $this->thread->stop();
        $this->thread->quit();
    }

    /**
     * @return string
     */
    public function getRecentResponse(): string
    {
        return $this->thread->getRecentResponse();
    }

    /**
     * @param string $command
     */
    public function sendCommand(string $command)
    {
        $this->thread->enqueueCommand($command);
    }
}