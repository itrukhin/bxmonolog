<?php
namespace App\Monolog;

class FormatHelper {

    /**
     * @param mixed $message
     * @return string
     */
    public static function stringfyMessage($message) {

        if($message instanceof \Throwable) {
            $message = (string) $message;
        } else if(is_array($message) || is_object($message)) {
            $message = json_encode((array) $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message = str_replace('\\u0000', '', $message);
        }
        return (string) $message;
    }

    /**
     * @param mixed $message
     * @param array $context
     * @return string
     */
    public static function stringfyTelegramMessage($message, array $context) {

        $source = (isset($context['source']) ? sprintf("<b>%s</b>", (string) $context['source']) : '');

        if($message instanceof \Throwable) {

            $message = sprintf("%s, <i>File: %s</i>", $message->getMessage(), $message->getFile());

        } else if(is_array($message) || is_object($message)) {

            $message = json_encode((array) $message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message = str_replace('\\u0000', '', $message);
            if(strlen($message) > 4000) {
                $message = substr($message, 0, 4000) . '...';
            }
            $message = sprintf("<code>%s</code>", $message);
        }

        if(!empty($source)) {
            return sprintf("%s: %s", $source, $message);
        } else {
            return $message;
        }
    }
}