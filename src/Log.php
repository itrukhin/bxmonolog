<?php
namespace App;

use App\Monolog\ArrayFormatter;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Error;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger;
use Monolog\Registry;
use Psr\Log\LoggerInterface;

class Log implements LoggerInterface {

    private $channel;
    private $minDebugLevel;

    /**
     * @param string $channel
     */
    public function __construct($channel = '') {

        $this->minDebugLevel = ($_ENV['APP_DEBUG_LEVEL'] ?: 'DEBUG');
        if(!empty($channel)) {
            $this->channel = $channel;
            //TODO: get channel options
        } else {
            $this->channel = ($_ENV['APP_LOG_BITRIX_CHANNEL'] ?: 'bitrix');
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function alert($message, $context = [])
    {
        try {
            $logger = $this->getLogger();
            $message = $this->formatMessage($message);
            $logger->alert($message, (array) $context);
        } catch (\Exception $e) {
            self::logInnerException($e);
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function critical($message, $context = [])
    {
        try {
            $logger = $this->getLogger();
            $message = $this->formatMessage($message);
            $logger->critical($message, (array) $context);
        } catch (\Exception $e) {
            self::logInnerException($e);
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function error($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::ERROR)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->error($message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function warning($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::WARNING)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->warning($message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function notice($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::NOTICE)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->notice($message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function info($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::INFO)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->info($message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::DEBUG)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->debug($message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    public function emergency($message, $context = [])
    {
        try {
            $logger = $this->getLogger();
            $message = $this->formatMessage($message);
            $logger->emergency($message, (array) $context);
        } catch (\Exception $e) {
            self::logInnerException($e);
        }
    }

    public function log($level, $message, $context = [])
    {
        $level = Logger::toMonologLevel($level);
        if ($this->isDebugEnabled($level)) {
            try {
                $logger = $this->getLogger();
                $message = $this->formatMessage($message);
                $logger->log($level, $message, (array) $context);
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    public function telegram($level, $message, $context = []) {

        $level = Logger::toMonologLevel($level);
        if (!$this->isDebugEnabled($level)) {
            return;
        }

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
            return;
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

        $sender->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message%"));

        $telegramLogger = new Logger('Telegram');
        $telegramLogger->pushHandler($sender);
        $telegramLogger->log($level, $message, $context);
    }

    /**
     * @param $message
     * @return string
     */
    private function formatMessage($message) {

        if($message instanceof \Exception || $message instanceof Error) {
            $message = (string) $message;
        } else if(is_array($message) || is_object($message)) {
            $message = json_encode((array) $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message = str_replace('\\u0000', '', $message);
        }
        return (string) $message;
    }

    /**
     * @return Logger
     */
    public function getLogger() {

        if(Registry::hasLogger($this->channel)) {
            return Registry::getInstance($this->channel);
        }
        $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
        $logPath .= $this->channel . '/' . date('Y-m-d') . '.log';
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

        $logger = new Logger($this->channel);
        $logger->pushHandler($handler);
        Registry::addLogger($logger, $this->channel, true);

        return $logger;
    }

    /**
     * @return int|mixed
     */
    private function getMinErrorLevel() {

        $levels = Logger::getLevels();

        if($this->minDebugLevel && isset($levels[$this->minDebugLevel])) {
            return $levels[$this->minDebugLevel];
        } else {
            return Logger::DEBUG;
        }
    }

    /**
     * @param int $level
     * @return bool
     */
    private function isDebugEnabled($level = 0)
    {
        if (defined('FORCE_DEBUG') && FORCE_DEBUG) {
            return true;
        }

        $min_level = $this->getMinErrorLevel();
        if($level >= $min_level) {
            return true;
        }
        return false;
    }

    /**
     * @param \Exception $exception
     */
    public static function logInnerException(\Exception $exception)
    {
        Debug::writeToFile((string) $exception, "", "inner_error.log");
    }

    public static function cleanLogs($daysAgo = 15) {

        $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
        $command = sprintf("find %s -mindepth 1 -type f -mtime +%d | xargs rm", $logPath, $daysAgo);
        exec($command);
        return sprintf('\App\Log::cleanLogs(%d);', $daysAgo);
    }
}
