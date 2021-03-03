# Скрипт для обработки запросов Протокола оператора Версии 3.5. Редакции 5. 19.01.2012 
Версия скрипта 11 ООО "Биллинговые системы"

## Включенные файлы: 
> 'payment.php' - обработка запросов 
> 'bisys.sql' - Создание таблиц для работы скриптов 
> 'test.html' - Форма для локального тестирования скрипта

## Установка 

Предполагается наличие у оператора установленных Apache, PHP и MySQL.

Запрос с файла bisys.sql создает следующие таблицы: 
> accounts - таблица со счетами пользователей 
> account_id - ИД счета 
> account - счет 
> status - Статус счета (1 - включен, остальные значения - отключен) 
> client_id - ID пользователя в базе оператора 
> balance - текущий баланс пользователя 
> payments - таблица с платежами 
> payment_id - идентификатор платежа у Оператора 
> agend_id - ID агента 
> agent_date – дата для сверки платежей. Используется дата получения платежа Агентом по часовому поясу Агента. 
> pay_num - уникальный номер платежа в базе агента 
> account_id - ИД счета 
> amount - сумма платежа в копейках 
> pay_date – дата платежа. Дата принятия платежа у клиента по часовому поясу в месте принятия платежа. 
> reg_date – дата регистрации платежа у Оператора 
> logs - таблица с логами 
> log_id - ИД лога 
> date - дата 
> ip - IP адрес, с которого пришел запрос 
> in_data - входные данные запроса 
> out_data - ответ на запрос 
> err_code - код ошибки 
> err_text - текст ошибки

В файле payment.php небоходимо прописать параметры подключения к БД (`$db_host`, `$db_user`, `$db_password`, `$db_name`). 
Также указывается пароль для генерации хеша и список разрешенных адресов для запросов. Оператор должен добавить свой код проведения платежа в функцию 'do_payment' в файле 'payment.php' Если пополнение платежа происходит внешним скриптом, то строчку 292 в файле 'payment.php' ('response(90, "Временная техническая ошибка");') можно расскоментировать. 
Оператор может изменить структуру таблиц и код обработки запросов при необходимости.

Готовый скипт можно протестировать локально из браузера с помощью файла 'test.html'. Вставляйте в поле XML-запросы и отправляйте. Пример запроса есть в протоколе. Для этого тестирования проверку подписи можно отключить.