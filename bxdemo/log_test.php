<?php
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$log = new \App\Log('test');


$log->info('log info message');
$log->debug($_SERVER);
$log->alert([1, 2]);
$log->notice(\Bitrix\Main\Application::getConnection());

try {
    throw new Exception('test exception');
} catch(Exception $e) {
    $log->error($e);
}
throw new Exception('test exception');


