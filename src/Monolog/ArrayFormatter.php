<?php
namespace App\Monolog;

use Monolog\Formatter\NormalizerFormatter;

class ArrayFormatter extends NormalizerFormatter
{
	/**
	 * {@inheritdoc}
	 */
	public function format(array $record)
	{
		/** @var \DateTime $date */
		$date = $record['datetime'];
		$output = array(sprintf("%s %s", $record['level_name'], $date->format("d.m.Y H:i:s")));
        $output[] = $record['message'];
		if(is_array($record['context'])) {
		    if(count($record['context']) == 1) {
                $output[] = current($record['context']);
            } else if(count($record['context']) > 1) {
                $output[] = print_r($record['context'], true);
            }
        } else if(!empty($record['context'])) {
            $output[] = $record['context'];
        }

		return join("\r\n", $output) . "\r\n------------------------------------------------------------------------\r\n";
	}
}
