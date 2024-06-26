<?php

namespace VM\Crontab;

class CrontabDispatcher
{
    /**
     * @var \VM\Application
     */
    private $app;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Scheduler
     */
    private $scheduler;


    public function __construct(\VM\Application $app)
    {
        $this->app = $app;
        $this->logger = $app->make('log');
        $this->scheduler = $app->make(Scheduler::class, ['app' => $app]);
    }

    public function handle(): void
    {
        try {
            printf('Crontab dispatcher schedule [%d] crontabs get going %ds...'.PHP_EOL, count($this->app->config->get('crontab')), 60 - date('s'));
            while (true) {
                if ($this->sleep()) {
                    break;
                }
                $crontabs = $this->scheduler->schedule();
                while (!$crontabs->isEmpty()) {
                    $crontab = $crontabs->dequeue();
                    \Swoole\Timer::after($crontab->time > 0 ? $crontab->time * 1000 : 1, [$crontab, 'execute']);
                }
            }
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            printf(PHP_EOL."%s".PHP_EOL, $throwable->getMessage());
        } finally {
            \Swoole\Timer::clearAll();
            \Swoole\Coroutine::sleep(5);
        }
    }

    

    protected function sleep()
    {
        $sleep   = 60 - date('s');
        if ($sleep > 0) {
            if ($this->app->make(Coordinator::class)->yield($sleep)) {
                return true;
            }
        }
        return false;
    }
}