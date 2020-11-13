# Адаптер [Monolog](https://github.com/Seldaek/monolog) для 1С-Битрикс

Адаптер позволяет организовать запись и хранение файлов логов в Битриксе. Основные возможности:
* Хранение всех логов в выделенной специальной папке на сервере
* Автоматическое создание вложенных папок для группировки файлов логов
* Ошибки ядра 1С-Битрикс пишутся во вложенную папку bitrix, которую можно переназначить
* Каждый день логи пишутся в отдельный файл вида YYYY-MM-DD.log
* Автоматическое удаление файлов логов старше N дней
* Конфигурирование через глобальные переменные $_ENV, например с помощью [Dotenv](https://dev.1c-bitrix.ru/community/webdev/user/42376/blog/39344/)
* Аварийный лог inner_error.log для ошибок самого логгера 

## Установка
```bash
composer require itrukhin/bxmonolog:dev-master
```
Предполагается, что у вас Битрикс уже умеет работать с автозагрузкой composer

## Настройка .settings.php
```php
'exception_handling' => 
  array (
    'value' => 
    array (
      'debug' => ((bool) $_ENV['APP_DEBUG']),
      'handled_errors_types' => E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
      'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED,
      'ignore_silence' => false,
      'assertion_throws_exception' => true,
      'assertion_error_type' => 256,
      'log' => 
      array (
        'class_name' => '\App\Monolog\ExceptionHandlerLog',
      ),
    ),
    'readonly' => false,
  ),
```

## Настройки окружения
* **APP_DEBUG** - включить/выключить отладку, испольщуется только в .settings.php
* **APP_DEBUG_LEVEL** - минимальный уровень, для которого будет выполняться запись в лог. По умолчанию DEBUG, это значит, что будут выводится все ошибки. Можно на проде, например, ограничить уровнем ERROR
* **APP_LOG_FOLDER** - папка логов, относительно DOCUMENT_ROOT. По умолчанию /log/
* **APP_LOG_BITRIX_CHANNEL** - подпапка логов по умолчанию. Если не задано, будет создана папка bitrix, относительно APP_LOG_FOLDER. Для записи своих логов рекомендуется явно указывать папку, так как в папку bitrix будут записываться ошибки ядра.

# Примеры использования
Код примеров есть в папке [examples](examples/)

При настроенном .settings.php (см. выше) все ошибки битрикса будут записываться в файл
```bash
DOCUMENT_ROOT/APP_LOG_FOLDER/APP_LOG_BITRIX_CHANNEL/YYYY-MM-DD.log
```
что, по умолчанию, соответствует пути /log/bitrix/ от корня сервера. Ошибки ядра имеют уровень CRITICAL

Для записи произвольных логов необходимо вначале создать экземпляр объекта лога
```php
$log = new \App\Log('test');
```
где test - имя папки, относительно APP_LOG_FOLDER, в которую будут писаться логи. Подапка при ее отсутствии будет создана автоматически. По возможности, будут установлены аттрибуты BX_DIR_PERMISSIONS

Далее для каждого уровня лога может быть вызван одноименный метод, например:

```php
$log->info('log info message');
$log->debug($_SERVER);
$log->alert([1, 2]);
$log->notice(\Bitrix\Main\Application::getConnection());
```
В результате мы получаем файлы логов с отформатированным содержимым, пример:

```
CRITICAL 12.11.2020 11:02:01
Bitrix\Main\DB\SqlQueryException: Mysql query error: []  in /var/www/html/bitrix/modules/main/classes/mysql/database.php:183
Stack trace:
#0 /var/www/html/bitrix/modules/statistic/classes/mysql/stoplist.php(223): CDatabaseMysql->Query()
#1 /var/www/html/bitrix/modules/main/classes/general/module.php(480): CStoplist::Check()
#2 /var/www/html/bitrix/modules/main/include.php(303): ExecuteModuleEventEx()
#3 /var/www/html/bitrix/modules/main/include/prolog_before.php(14): require_once('/var/www/html/b...')
#4 /var/www/html/local/scripts/1c_parser.php(14): require('/var/www/html/b...')
#5 {main}
------------------------------------------------------------------------
CRITICAL 12.11.2020 11:02:01
Bitrix\Main\DB\SqlQueryException: Mysql query error: []  in /var/www/html/bitrix/modules/main/classes/mysql/database.php:183
Stack trace:
#0 /var/www/html/bitrix/modules/statistic/classes/mysql/stoplist.php(223): CDatabaseMysql->Query()
#1 /var/www/html/bitrix/modules/main/classes/general/module.php(480): CStoplist::Check()
#2 /var/www/html/bitrix/modules/main/include.php(303): ExecuteModuleEventEx()
#3 /var/www/html/bitrix/modules/main/include/prolog_before.php(14): require_once('/var/www/html/b...')
#4 /var/www/html/local/scripts/yml_cli.php(17): require('/var/www/html/b...')
#5 {main}
------------------------------------------------------------------------
```
# Очистка логов
Для периодической очистки логов можно использовать метод 
```php
\App\Log::cleanLogs(15);
```
Который будет удалять файлы логов старше 15 дней. Метод возвращает строку вызова самого себя, поэтому может быть добавлен в агенты Битрикса.
