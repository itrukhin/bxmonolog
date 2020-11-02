<?php
namespace App;

use App\Monolog\ArrayFormatter;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Error;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Registry;

class Log {

    private $channel;
    private $minDebugLevel;

    /**
     * Log constructor.
     * @param null $channel
     */
    public function __construct($channel = null) {

        $this->minDebugLevel = ($_ENV['APP_DEBUG_LEVEL'] ?: 'DEBUG');
        if($channel) {
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
            if ($logger) {
                $message = $this->formatMessage($message);
                $logger->alert($message, $context);
            }
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
            if ($logger) {
                $message = $this->formatMessage($message);
                $logger->critical($message, $context);
            }
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
        if (self::isDebugEnabled(Logger::ERROR)) {
            try {
                $logger = $this->getLogger();
                if ($logger) {
                    $message = $this->formatMessage($message);
                    $logger->error($message, $context);
                }
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
        if (self::isDebugEnabled(Logger::WARNING)) {
            try {
                $logger = $this->getLogger();
                if ($logger) {
                    $message = $this->formatMessage($message);
                    $logger->warning($message, $context);
                }
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
        if (self::isDebugEnabled(Logger::NOTICE)) {
            try {
                $logger = $this->getLogger();
                if ($logger) {
                    $message = $this->formatMessage($message);
                    $logger->notice($message, $context);
                }
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
        if (self::isDebugEnabled(Logger::INFO)) {
            try {
                $logger = $this->getLogger();
                if ($logger) {
                    $message = $this->formatMessage($message);
                    $logger->info($message, $context);
                }
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
        if (self::isDebugEnabled(Logger::DEBUG)) {
            try {
                $logger = $this->getLogger();
                if ($logger) {
                    $message = $this->formatMessage($message);
                    $logger->debug($message, $context);
                }
            } catch (\Exception $e) {
                self::logInnerException($e);
            }
        }
    }

    /**
     * @param $message
     * @return false|string
     */
    private function formatMessage($message) {

        if($message instanceof \Exception || $message instanceof Error) {
            $message = (string) $message;
        } else if(is_array($message) || is_object($message)) {
            $message = json_encode((array) $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message = str_replace('\\u0000', '', $message);
        }
        return $message;
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
            mkdir($logDir, BX_DIR_PERMISSIONS, true);
        }

        $handler = new StreamHandler($logPath);
        $handler->setFormatter(new ArrayFormatter());

        $logger = new Logger($this->channel);
        if($handler) $logger->pushHandler($handler);
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
        $min_level = self::getMinErrorLevel();
        if($level >= $min_level) {
            return true;
        }
        return false;
    }

    /**
     * @param \Exception $exception
     */
    public function logInnerException(\Exception $exception)
    {
        Debug::writeToFile((string) $exception, "", "inner_error.log");
    }

    public static function cleanLogs($daysAgo = 15) {

        $logPath = $_SERVER['DOCUMENT_ROOT'] . ($_ENV['APP_LOG_FOLDER'] ?: '/log/');
        $command = sprintf("find %s -mindepth 1 -type f -mtime +%d | xargs rm", $logPath, $daysAgo);
        exec($command);
    }
}