<?php

namespace app\helpers;

use Yii;

class DiscordNotifier
{
    private const CONNECT_TIMEOUT_S = 5;
    private const TIMEOUT_S = 10;

    /**
     * Send a message payload to a Discord webhook.
     *
     * @param string $webhookUrl Discord webhook URL
     * @param array $payload Discord webhook payload (e.g. ['content' => ..., 'embeds' => [...]])
     * @return bool True on success (HTTP 2xx), false otherwise
     */
    public static function send(string $webhookUrl, array $payload): bool
    {
        if (empty($webhookUrl)) {
            return false;
        }

        try {
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_S,
                CURLOPT_TIMEOUT => self::TIMEOUT_S,
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $error !== '') {
                Yii::warning("Discord webhook request failed: {$error}", 'discord');
                return false;
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                Yii::warning("Discord webhook returned HTTP {$statusCode}: {$response}", 'discord');
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error('Discord webhook exception: ' . $e->getMessage(), 'discord');
            return false;
        }
    }
}
