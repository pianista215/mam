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
        try {
            // Try to convert model to array to be logged
            $safeData = json_encode(
                $this->convertModelsToArray($data),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($safeData === false) {
                throw new \Exception(json_last_error_msg()); // Captura error de json_encode
            }
        } catch (\Throwable $e) {
            Yii::error('Error serializing log data: ' . $e->getMessage(), 'log');
            $safeData = $data;
        }

        Yii::{$level}(
            $message . ' ' . print_r($safeData, true),
            'mam_'. static::class . '::' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']
        );
    }

    private function convertModelsToArray($data)
    {
        if ($data instanceof \yii\db\ActiveRecord) {
            return $data->toArray();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->convertModelsToArray($value);
            }
        }

        return $data;
    }
}
