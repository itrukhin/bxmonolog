<?php
namespace App;

use App\Monolog\FormatHelper;
use App\Monolog\LoggerFactory;
use Bitrix\Main\Diag\Debug;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Log implements LoggerInterface {

    private $channel;

    /**
     * @param string $channel
     */
    public function __construct($channel = '') {

        if(!empty($channel)) {
            $this->channel = $channel;
        } else {
            $this->channel = ($_ENV['APP_LOG_BITRIX_CHANNEL'] ?: 'bitrix');
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function alert($message, $context = [])
    {
        try {
            $logger = LoggerFactory::getInstance($this->channel, $context);
            $message = FormatHelper::stringfyMessage($message);
            $logger->alert($message, (array) $context);
        } catch (\Exception $e) {
            // there may be too many open files
            // $this->logInnerException($e);
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function critical($message, $context = [])
    {
        try {
            $logger = LoggerFactory::getInstance($this->channel, $context);
            $message = FormatHelper::stringfyMessage($message);
            $logger->critical($message, (array) $context);
        } catch (\Exception $e) {
            $this->logInnerException($e);
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function error($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::ERROR)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->error($message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function warning($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::WARNING)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->warning($message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function notice($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::NOTICE)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->notice($message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function info($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::INFO)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->info($message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        if ($this->isDebugEnabled(Logger::DEBUG)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->debug($message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $message
     * @param array $context
     */
    public function emergency($message, $context = [])
    {
        try {
            $logger = LoggerFactory::getInstance($this->channel, $context);
            $message = FormatHelper::stringfyMessage($message);
            $logger->emergency($message, (array) $context);
        } catch (\Exception $e) {
            $this->logInnerException($e);
        }
    }

    /**
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     */
    public function log($level, $message, $context = [])
    {
        if ($this->isDebugEnabled($level)) {
            try {
                $logger = LoggerFactory::getInstance($this->channel, $context);
                $message = FormatHelper::stringfyMessage($message);
                $logger->log($level, $message, (array) $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     */
    public function telegram($level, $message, $context = []) {

        if (!$this->isDebugEnabled($level)) {
            return;
        }
        $this->log($level, $message, $context);

        $logger = LoggerFactory::getInstance(LoggerFactory::TELEGRAM_CHANNEL, $context);

        if($logger) {
            try {
                $message = FormatHelper::stringfyTelegramMessage($message, (array) $context);
                $logger->log($level, $message, $context);
            } catch (\Exception $e) {
                $this->logInnerException($e);
            }
        }
    }

    /**
     * @param mixed $level
     * @return bool
     */
    private function isDebugEnabled($level)
    {
        if (defined('FORCE_DEBUG') && FORCE_DEBUG) {
            return true;
        }

        $level = Logger::toMonologLevel($level);

        $minDebugLevel = ($_ENV['APP_DEBUG_LEVEL'] ?: LogLevel::DEBUG);
        $minDebugLevel = Logger::toMonologLevel($minDebugLevel);

        if($level >= $minDebugLevel) {
            return true;
        }
        return false;
    }

    /**
     * @param \Exception $exception
     */
    protected function logInnerException(\Exception $exception)
    {
        if(class_exists('\Bitrix\Main\Diag\Debug')) {
            Debug::writeToFile((string) $exception, "", "monolog_error.log");
        } else {
            $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
            $logPath = rtrim($logPath, "/");
            file_put_contents($logPath . '/monolog_error.log', (string) $exception);
        }
    }

    public static function cleanLogs($daysAgo = 15) {

        $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
        $command = sprintf("find %s -mindepth 1 -type f -mtime +%d | xargs rm", $logPath, $daysAgo);
        exec($command);
        return sprintf('\App\Log::cleanLogs(%d);', $daysAgo);
    }
}
