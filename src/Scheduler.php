<?php

namespace VM\Crontab;

use Carbon\Carbon;
use Psr\Container\ContainerInterface;
use Swoole\Timer;

class Scheduler
{
     /**
     * @var ContainerInterface|\VM\Application
     */
    private $app;

    /**
     * @var \VM\Server\ParserTime
     */
    private $parser;

    /**
     * @var \SplQueue
     */
    protected \SplQueue $schedules;

    public function __construct(ContainerInterface $app, ParserTime $parser)
    {
        $this->app = $app;
        $this->parser    = $parser;
        $this->schedules = new \SplQueue();
    }

    public function schedule(): \SplQueue
    {
        foreach ($this->getSchedules() ?? [] as $schedule ) {
            $this->schedules->enqueue( $schedule );
        }
        return $this->schedules;
    }

    protected function getSchedules(): array
    {
        $result  = [];
        foreach ($this->app->config->get('crontab', [])  as $crontab) {
            $crontab = $this->app->make($crontab);
            $time = $this->parser->parse($crontab->crontab, time());
            if ($time) {
                foreach ( $time as $t ) {
                    $crontab->runat = Carbon::now()->diffInRealSeconds($t, false);
                    $result[] = clone $crontab;
                }
            }
        }
        return $result;
    }
}