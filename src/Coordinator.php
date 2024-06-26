<?php

namespace VM\Crontab;

class Coordinator
{
    /**
     * @var Channel
     */
    private $channel;

    public function __construct()
    {
        $this->channel = new Channel( 1 );
    }

    public function yield($timeout = -1): bool
    {
        $this->channel->pop( (float)$timeout );
        return $this->channel->errCode === SWOOLE_CHANNEL_CLOSED;
    }

    public function resume(): void
    {
        $this->channel->close();
    }
}
