<?php

namespace app\config;

use Yii;

class Config
{
    const CACHE_KEY = 'app_config';

    public static function get($key, $default = null)
    {
        $config = Yii::$app->cache->get(self::CACHE_KEY);

        if ($config === false) {
            $config = Yii::$app->db->createCommand('SELECT `key`, `value` FROM config')
                ->queryAll(\PDO::FETCH_KEY_PAIR);
            Yii::$app->cache->set(self::CACHE_KEY, $config);
        }

        return isset($config[$key]) ? $config[$key] : $default;
    }

    public static function set($key, $value)
    {
        Yii::$app->db->createCommand()
            ->upsert('config', ['key' => $key, 'value' => $value])
            ->execute();

        Yii::$app->cache->delete(self::CACHE_KEY);
    }

    public static function delete($key)
    {
        Yii::$app->db->createCommand()
            ->delete('config', ['key' => $key])
            ->execute();

        Yii::$app->cache->delete(self::CACHE_KEY);
    }
}