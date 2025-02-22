<?php
namespace app\helpers;

use Yii;

trait LoggerTrait
{
    protected function logInfo($message, $data = [])
    {
        $this->log('info', $message, $data);
    }

    protected function logWarn($message, $data = [])
    {
        $this->log('warning', $message, $data);
    }

    protected function logError($message, $data = [])
    {
        $this->log('error', $message, $data);
    }

    private function log($level, $message, $data = [])
    {
        Yii::{$level}(
            $message . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            static::class . '::' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']
        );
    }
}
