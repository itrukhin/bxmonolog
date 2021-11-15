<?php
namespace App\Monolog;

use App\Log;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Diag\ExceptionHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class ExceptionHandlerLog extends \Bitrix\Main\Diag\ExceptionHandlerLog {

    /**
     * @var Logger
     */
	protected $logger;
	/**
     * @var callable
     */
	protected $context;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (isset($options['context']))
        {
            $this->context = $options['context'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($exception, $logType)
    {
        try {
            $log = new Log();
            if(is_callable($this->context)) {
                try {
                    $this->context = call_user_func($this->context, $exception);
                } catch(\Exception $e) {
                    self::logInnerException(new \Exception('Can not call ' . (string) $this->context));
                }
            }
            if(!is_array($this->context)) {
                $this->context = (!empty($this->context) ? [$this->context] : []);
            }
            if($logType == \Bitrix\Main\Diag\ExceptionHandlerLog::LOW_PRIORITY_ERROR) {
                $log->error($exception, $this->context);
            } else {
                $this->context['source'] = self::logTypeToString($logType);
                $log->telegram(LogLevel::CRITICAL, $exception, $this->context);
            }

        } catch(\Exception $e) {
            self::logInnerException($e);
        }
    }

    /**
     * @param \Exception $exception
     */
    protected static function logInnerException(\Exception $exception)
    {
        Debug::writeToFile((string) $exception, "", "inner_error.log");
    }
}