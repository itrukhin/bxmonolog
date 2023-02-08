<?php
namespace App\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger;
use Monolog\Registry;

class LoggerFactory {

    const TELEGRAM_CHANNEL = 'telegram-messenger';

    public static function getInstance($channel, $context) {

        $key = md5(json_encode([$channel, $context]));
        if(Registry::hasLogger($key)) {
            return Registry::getInstance($key);
        }

        if($channel == self::TELEGRAM_CHANNEL) {

            if(isset($_ENV['APP_LOG_TELEGRAM'])) {
                $APP_LOG_TELEGRAM = trim(strtolower($_ENV['APP_LOG_TELEGRAM']));
                if($APP_LOG_TELEGRAM !== 'on' && $APP_LOG_TELEGRAM !== 'true' && $APP_LOG_TELEGRAM !== '1') {
                    return null;
                }
            } else {
                return null;
            }

            if(!isset($_ENV['APP_LOG_TELEGRAM_KEY']) || empty($_ENV['APP_LOG_TELEGRAM_KEY'])) {
                return null;
            }
            if(!isset($_ENV['APP_LOG_TELEGRAM_CHATID']) || empty($_ENV['APP_LOG_TELEGRAM_CHATID'])) {
                return null;
            }

            if(!class_exists('\Monolog\Handler\TelegramBotHandler')) {
                return null;
            }

            $sender = new TelegramBotHandler($_ENV['APP_LOG_TELEGRAM_KEY'], $_ENV['APP_LOG_TELEGRAM_CHATID']);

            if(isset($context['parse_mode']) && !empty($context['parse_mode'])) {
                $sender->setParseMode($context['parse_mode']);
                unset($context['parse_mode']);
            } else {
                $sender->setParseMode('HTML');
            }
            if(isset($context['disable_notification']) && $context['disable_notification'] === true) {
                $sender->disableNotification(true);
                unset($context['disable_notification']);
            }
            if(isset($context['disable_preview']) && $context['disable_preview'] === false) {
                $sender->disableWebPagePreview(false);
                unset($context['disable_preview']);
            } else {
                $sender->disableWebPagePreview(true);
            }

            $sender->setFormatter(new LineFormatter("%level_name%! %message%"));

            $telegramLogger = new Logger(self::TELEGRAM_CHANNEL);
            $telegramLogger->pushHandler($sender);
            Registry::addLogger($telegramLogger, $key, true);

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
            Registry::addLogger($logger, $key, true);

            return $logger;
        }
    }
}
