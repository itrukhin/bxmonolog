# Адаптер [Monolog](https://github.com/Seldaek/monolog) для 1С-Битрикс

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/itrukhin/bxmonolog/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/itrukhin/bxmonolog/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/itrukhin/bxmonolog/badges/build.png?b=master)](https://scrutinizer-ci.com/g/itrukhin/bxmonolog/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/itrukhin/bxmonolog/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

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
* **APP_DEBUG** - включить/выключить отладку, используется только в `.settings.php`
* **APP_DEBUG_LEVEL** - минимальный уровень, для которого будет выполняться запись в лог. По умолчанию `DEBUG`, это значит, что будут выводиться все ошибки. Можно на проде, например, ограничить уровнем `ERROR`
* **APP_LOG_FOLDER** - папка логов, относительно `DOCUMENT_ROOT`. По умолчанию `/log/`
* **APP_LOG_BITRIX_CHANNEL** - подпапка логов по умолчанию. Если не задано, будет создана папка `bitrix`, относительно `APP_LOG_FOLDER`. Для записи своих логов рекомендуется явно указывать папку, так как в папку `bitrix` будут записываться ошибки ядра.

# Примеры использования
Код примеров есть в папке [examples](examples/)

При настроенном `.settings.php` (см. выше) все ошибки битрикса будут записываться в файл
```bash
DOCUMENT_ROOT/APP_LOG_FOLDER/APP_LOG_BITRIX_CHANNEL/YYYY-MM-DD.log
```
что, по умолчанию, соответствует пути `/log/bitrix/` от корня сервера. Ошибки ядра имеют уровень `CRITICAL`

Для записи произвольных логов необходимо вначале создать экземпляр объекта лога
```php
$log = new \App\Log('test');
```
где test - имя папки, относительно `APP_LOG_FOLDER`, в которую будут писаться логи. Подапка, при ее отсутствии, будет создана автоматически. По возможности, будут установлены аттрибуты `BX_DIR_PERMISSIONS`

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
# Отправка ошибок в Telegram
Для оперативной реакции на ошибки, необходимо узнавать о них сразу, как только они возникли. Одним из удобных способов 
является отправка ошибок в чат Telegram. О возникновении ошибки на сайте немедленно будут проинформированы все участники чата.

## Настройка Telegram
Необходимо создать бота в телеграм с помощью [@BotFather](https://core.telegram.org/bots#3-how-do-i-create-a-bot). Для бота мы получим 
API Token вида `000000000:XXXXXXXXXXXXXXXXXXXX`. Далее нужно создать чат, в который бот будет писать сообщения, и добавить бота администратором в этот чат.
Второй параметр, который нам необходимо получить - это ChatID. Получить его можно выполнив запрос вида `https://api.telegram.org/botXXX:YYYYY/getUpdates`,
где `XXX:YYYYY` - это API Token бота. В ответ будет json, из которого нужно получить ChatID.
### Пример ответа
```json
{"ok":true,"result":[{"update_id":81329501,
"message":{"message_id":975,"from":{"id":962548471,"is_bot":false,"first_name":"Trajano","last_name":"Roberto","username":"TrajanoRoberto","language_code":"en"},"chat":{"id":-1001202656383,"title":"R\u00e1dioRN - A voz da na\u00e7\u00e3o!","type":"supergroup"},"date":1587454914,"left_chat_participant":{"id":1215098445,"is_bot":true,"first_name":"MediaFlamengoRawBot","username":"MediaFlamengoRawBot"},"left_chat_member":{"id":1215098445,"is_bot":true,"first_name":"MediaFlamengoRawBot","username":"MediaFlamengoRawBot"}}},{"update_id":81329502,
"message":{"message_id":976,"from":{"id":962548471,"is_bot":false,"first_name":"Trajano","last_name":"Roberto","username":"TrajanoRoberto","language_code":"en"},"chat":{"id":-1001202656383,"title":"R\u00e1dioRN - A voz da na\u00e7\u00e3o!","type":"supergroup"},"date":1587454932,"new_chat_participant":{"id":1215098445,"is_bot":true,"first_name":"MediaFlamengoRawBot","username":"MediaFlamengoRawBot"},"new_chat_member":{"id":1215098445,"is_bot":true,"first_name":"MediaFlamengoRawBot","username":"MediaFlamengoRawBot"},"new_chat_members":[{"id":1215098445,"is_bot":true,"first_name":"MediaFlamengoRawBot","username":"MediaFlamengoRawBot"}]}}]}
```
нас интересует **"chat":{"id":-1001202656383,"title"...** ChatID это `-1001202656383`

## Настройка BxMonolog
За настройку параметров отправки сообщений в Telegram отвечают следующие параметры $_ENV
* **APP_LOG_TELEGRAM** - включить/выключить отправку в Telegram. Должно быть установлено в **1**, или **true**, или **on**
* **APP_LOG_TELEGRAM_KEY** - API Token бота
* **APP_LOG_TELEGRAM_CHATID** - ChatID

## Отправка в Telegram
Для отправки сообщение в телеграм, реализован метод telegram, аналогичный методу log в PSR-3
```php
$log = new \App\Log('test');
try {
    // my code
} catch(Exception $e) {
    $log->telegram(\Psr\Log\LogLevel::ERROR, $e, ['source' => 'my code error'])
}
```
Результатом будет запись ошибки в лог и сообщение в Telegram, начинающееся с _my code error_ и содержащее текст ошибки, имя файла. Также можно писать любой текст.
По умолчанию включен формат отправки HTML, включены уведомления, отключена ссылка на просмотр сообщения в вебе. Указывать source не обязательно. 

### Настройки, которые можно передавать через массив context
* source - начало сообщения в Telegram
* parse_mode - по умолчанию HTML, но может быть null, Markdown, MarkdownV2
* disable_notification - boolean, по умолчанию false
* disable_preview - boolean, по умолчанию true

Также можно получить экземпляр логгера, с настройками по умолчанию, только для отправки сообщений в Telegram
```php
$log = new \App\Log('telegram-messenger');
```

# Очистка логов
Для периодической очистки логов можно использовать метод 
```php
\App\Log::cleanLogs(15);
```
Который будет удалять файлы логов старше 15 дней. Метод возвращает строку вызова самого себя, поэтому может быть добавлен в агенты Битрикса.
