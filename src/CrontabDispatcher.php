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
            while (true) {
                if ($this->sleep()) {
                    break;
                }
                $crontabs = $this->scheduler->schedule();
                echo date('Y-m-d H:i:s', time()). 'Crontab dispatcher dispatch ' . count($crontabs) .'crontabs.' . PHP_EOL;
                while (!$crontabs->isEmpty()) {
                    $crontab = $crontabs->dequeue();
                    $crontab->execute();
                }
            }
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            echo $throwable->getMessage();
        } finally {
            \Swoole\Timer::clearAll();
            \Swoole\Coroutine::sleep(5);
        }
    }

    

    protected function sleep()
    {
        $current = date('s', time());
        $sleep   = 60 - $current;
        echo 'Crontab dispatcher sleep ' . $sleep . 's.';
        if ($sleep > 0) {
            if ($this->app->make(Coordinator::class)->yield($sleep)) {
                return true;
            }
        }
        return false;
    }
}