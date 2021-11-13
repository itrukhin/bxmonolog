<?php
namespace App\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger;
use Monolog\Registry;

class LoggerFactory {

    public static function getInstance($channel, $context) {

        if(Registry::hasLogger($channel)) {
            return Registry::getInstance($channel);
        }

        if($channel == 'telegram-messenger') {

            $isEnabled = false;
            if(isset($_ENV['APP_LOG_TELEGRAM'])) {
                $APP_LOG_TELEGRAM = trim(strtolower($_ENV['APP_LOG_TELEGRAM']));
                if($APP_LOG_TELEGRAM === 'on' || $APP_LOG_TELEGRAM === 'true' || $APP_LOG_TELEGRAM === '1') {
                    $isEnabled = true;
                }
            }

            if(!isset($_ENV['APP_LOG_TELEGRAM_KEY']) || empty($_ENV['APP_LOG_TELEGRAM_KEY'])) {
                $isEnabled = false;
            }
            if(!isset($_ENV['APP_LOG_TELEGRAM_CHANNEL']) || empty($_ENV['APP_LOG_TELEGRAM_CHANNEL'])) {
                $isEnabled = false;
            }

            if(!$isEnabled) {
                return null;
            }

            $sender = new TelegramBotHandler($_ENV['APP_LOG_TELEGRAM_KEY'], $_ENV['APP_LOG_TELEGRAM_CHANNEL']);

            if(isset($context['parse_mode']) && !empty($context['parse_mode'])) {
                $sender->setParseMode($context['parse_mode']);
                unset($context['parse_mode']);
            }
            if(isset($context['disable_notification']) && $context['disable_notification'] === true) {
                $sender->disableNotification(true);
                unset($context['disable_notification']);
            }
            if(isset($context['disable_preview']) && $context['disable_preview'] === true) {
                $sender->disableWebPagePreview(true);
                unset($context['disable_preview']);
            }

            $sender->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message%", "d.m.Y H:i:s"));

            $telegramLogger = new Logger('Telegram');
            $telegramLogger->pushHandler($sender);
            Registry::addLogger($telegramLogger, $channel, true);

            return $telegramLogger;

        } else {

            $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
            $logPath .= $channel . '/' . date('Y-m-d') . '.log';
            $logDir = pathinfo($logPath, PATHINFO_DIRNAME);
            if(!file_exists($logDir)) {
                $mode = 0775;
                if(defined('BX_DIR_PERMISSIONS') && BX_DIR_PERMISSIONS) {
                    $mode = BX_DIR_PERMISSIONS;
                }
                mkdir($logDir, $mode, true);
            }

            $handler = new StreamHandler($logPath);
            $handler->setFormatter(new ArrayFormatter());

            $logger = new Logger($channel);
            $logger->pushHandler($handler);
            Registry::addLogger($logger, $channel, true);

            return $logger;
        }
    }
}
