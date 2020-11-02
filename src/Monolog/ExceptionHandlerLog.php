<?php
namespace App\Monolog;

use App\Log;
use Bitrix\Main\Config\Option;
use Monolog\Logger;

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
        if (isset($options['context']) && is_callable($options['context']))
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
                    $context = call_user_func($this->context, $exception);
                } catch(\Exception $e) {
                    $log->logInnerException(new \Exception('Can not call ' . $this->context));
                }
            }
            $log->critical($exception, (array) $this->context);
        } catch(\Exception $e) {
            $log->logInnerException($e);
        }
    }
}