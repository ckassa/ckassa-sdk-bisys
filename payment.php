<?php

/*
Скрипт для обработки запросов Протокола оператора Версии 3.5. Редакции 5. 19.01.2012
Версия скрипта 11
ООО "Биллинговые системы"
http://bisys.ru/
*/


$agent_id = 1;		// Идентификатор агента в базе платежей
$password = "test1234";	// Пароль для генерации подписи запросов
$allowed_ips = "192.168.0.1,127.0.0.1,94.138.149.210,94.138.149.36,89.250.213.20,89.250.213.22,89.250.209.99,94.138.149.32"; // Список разрешенных адресов для запросов, перечисляются через запятую
$check_sign = TRUE; // Проверять ли подпись

// Параметры подключения к БД
$db_host = "localhost"; 
$db_user = "root"; 
$db_password = ""; 
$db_name = "bisys";

$in_data = "";		// Входные данные запроса
$out_data = "";		// Выходные данные запроса
$err_code_g = "";	// Код ошибки
$err_text_g = "";	// Текст ошибки
$query_sign = "";	// Подпись запроса

// Извлечение тега из строки
function get_tag_content($string, $tagname)
{
	$pattern = "|<".$tagname.">(.+)</".$tagname."|si";
	preg_match($pattern, $string, $matches);
	return $matches[1];
}

// Формирование тега вида <tagname>value</tagname>
// Если $newline истинно, то в начало добавляется переход на новую строку
function tag_format($tagname, $value, $newline = TRUE)
{
	if ($newline)
		return "\n<".$tagname.">".$value."</".$tagname.">"; else
		return "<".$tagname.">".$value."</".$tagname.">"; 
}

// Генерация ответа сервера на запрос
function response(
   $err_code,    // Код ошибки
   $err_text,    // Текст ошибки
   $params = "",    // Параметры
   $signed = TRUE    // Подписывать ли ответ
)
{
   // Записывам выходные данные в глобальные переменные, для дальнейшего сохранения их в логах
   global $out_data;
   global $err_code_g;
   global $err_text_g;
   global $password;
   $err_code_g = $err_code;
   $err_text_g = $err_text;
   global $query_sign;

   if ($params == "")
       $params = tag_format("err_code", $err_code, FALSE).tag_format("err_text", $err_text); else
       $params = tag_format("err_code", $err_code, FALSE).tag_format("err_text", $err_text).$params;

   if ($signed == TRUE)
       $sign = tag_format("sign", strtoupper(md5($params.$query_sign.$password)));
   else
       $sign = "";

   $out_data = "<?xml version=\"1.0\" encoding=\"windows-1251\"?><response><params>".$params."</params>".$sign."</response>";
   echo $out_data;        // Выводим ответ
} 


// Проверка параметров платежа
function check_payment(
	$account,	// Счет
	$pay_amount,	// Сумма
	$params,	// Параметры
	&$balance	// Баланс
)
{
	// Проверить существование указанного номера
	
	$sql_result = mysql_query("SELECT * FROM accounts WHERE account = ".$account." AND status = 1");
	if (!$sql_result)
		return 90;

	$row = mysql_fetch_assoc($sql_result);
	$account_id = (int)$row['account_id']; 
	if ($account_id == 0)
		return 20;

	// Проверить $pay_amount и другие параметры при необоходимости

	if ($pay_amount == 0)
		$pay_amount = 10000;

	if (!(($pay_amount >= 100) and ($pay_amount <= 1500000)))
		return 29;

	// Вернуть баланс, если возможно
	$balance = $row['balance'];
	return 0;
}

// Проведение платежа
function do_payment(
	$account,	// Счет
	$pay_amount,	// Сумма
	$pay_id,	// Уникальный номер платежа
	$pay_date,	// Дата платежа
	$params,	// Параметры
	&$account_id	// ID счета в базе
)
{
	// Проверить существование указанного номера
	
	$sql_result = mysql_query("SELECT * FROM accounts WHERE account = ".$account." AND status = 1");
	if (!$sql_result)
		return 90;
	$row = mysql_fetch_assoc($sql_result);
	$account_id = (int)$row['account_id']; 
	if ($account_id == 0)
		return 20;

	if (!(($pay_amount >= 100) and ($pay_amount <= 1500000)))
		return 29;

	$client_id = $row['client_id']; 

	// Провести платеж, вернуть 0 в случае успеха, 90 в случае ошибки
	// [!] Здесь необходимо вставить свой код пополнения баланса указанного счета


	return 0;
}

// Проверка разрешенных IP для доступа
$ips = explode(",",$allowed_ips);
if (array_search($_SERVER["REMOTE_ADDR"],$ips) === FALSE)
{
	response(10, "Запрос выполнен с неразрешенного адреса", "", FALSE);
	exit;
};

if (!isset($_POST['params']))
{
	response(11, "Указаны не все необходимые параметры", "", FALSE);
	exit;
}

$query = $_POST['params'];
$in_data = $query;
$params = get_tag_content($query, "params");
$query_sign = get_tag_content($query, "sign");
$sign = md5($params.$password);

// Проверка подписи
if ($check_sign and (($query_sign == "") or (strtoupper($sign) <> strtoupper($query_sign))))
{
	response(13, "Неверная цифровая подпись", "", FALSE);
	exit;
}

$act = (int)get_tag_content($params, "act");
if (($act == "") or ($act == 0))
{
	response(11, "Указаны не все необходимые параметры", "");
	exit;
}

// Проверка доступа к базе

$link = mysql_connect($db_host,$db_user,$db_password);
if (!$link)
{
	response(90, "Временная техническая ошибка [1]");
	exit;
}

$db_selected = mysql_select_db($db_name);
if (!$db_selected)
{
	response(90, "Временная техническая ошибка [2]");
	exit;
}

mysql_query("SET NAMES 'cp1251'");
mysql_query("SET CHARACTER SET 'cp1251'");

switch ($act)
{
	// Проверка параметров платежа
	case 1:
		$account = mysql_escape_string(get_tag_content($params, "account"));
		if ($account <> "")
		{
			$pay_amount = (int)get_tag_content($params, "pay_amount");
			$result = check_payment($account, $pay_amount, $params, $balance); 
			switch ($result)
			{

				case 0:
					$params = tag_format("account",$account, FALSE);
					if ($balance <> "")
						$params	= $params.tag_format("balance", number_format($balance/100, 2, '.', ''));
					response(0, "OK", $params);
				break;
				case 20:
					response(20, "Указанный номер счета отсутствует");
				break;
				case 21:
					response(21, "Запрещены платежи на указанный номер счета");
				break;
				case 22:
					response(22, "Запрещены платежи для указанной услуги");
				break;
				case 23:
					response(23, "Запрещены платежи для указанного агента");
				break;
				case 29:
					response(29, "Неверная сумма платежа");
				break;
				case 90:
					response(90, "Временная техническая ошибка");
				break;
				default:
					response(99, "Неизвестный ответ функции проверки параметров платежа");
			}
		} else response(11, "Указаны не все необходимые параметры");	
		break;
	// Проведение платежа
	case 2:
		$account = mysql_escape_string(get_tag_content($params, "account"));

		if ($account <> "")
		{
			$pay_amount = (int)get_tag_content($params, "pay_amount");
			$pay_id  = mysql_escape_string(get_tag_content($params, "pay_id"));
			$pay_date  = get_tag_content($params, "pay_date");
			$agent_date  = get_tag_content($params, "agent_date");

			if ($pay_id == "")
			{
				response(11, "Указаны не все необходимые параметры");
				exit;
			}
			
			$sql_result = mysql_query("SELECT payments.*, accounts.account FROM payments, accounts WHERE payments.account_id = accounts.account_id AND accounts.status = 1 AND agent_id = ".$agent_id." AND pay_num = '".$pay_id."'");
			if (!$sql_result)
			{
				response(90, "Временная техническая ошибка");
				exit;
			}

			$row = mysql_fetch_assoc($sql_result);
			$reg_id = (int)$row['payment_id']; 
	
			// Проверка на дублирование
			if ($reg_id <> 0)
			{
				$pay_amount_db = $row['amount'];
				$account_db = $row['account'];
//				$reg_id = $row['payment_id'];
				$reg_date = $row['reg_date'];
				$params = tag_format("account",$account, FALSE);

				if (($account == $account_db) and ($pay_amount == $pay_amount_db))
				{
					$params	= $params.tag_format("reg_id",$reg_id).tag_format("reg_date", date("Y-m-d\TH:i:s",strtotime($reg_date)));
					response(1, "Платеж уже был проведен", $params);
				} else
				{
					response(30, "Был другой платеж с указанным номером", $params);
				}
			} else
			{
				$result = do_payment($account, $pay_amount, $pay_id, $pay_date, $params, $account_id); 
				switch ($result)
				{
					case 0:
						$params = tag_format("account",$account, FALSE);
						mysql_query("INSERT INTO payments (agent_id, pay_num, account_id, amount, pay_date, reg_date, agent_date) VALUES ('".$agent_id."', '".$pay_id."', '".$account_id."', ".$pay_amount.", '".$pay_date."', NOW(), '".$agent_date."'  ) ");
						$reg_id =  mysql_insert_id();
						$sql_result = mysql_query("SELECT * FROM payments WHERE payment_id = ".$reg_id);
						if ((!$sql_result) or ($reg_id == 0))
						{
							// Ошибка при добавлении платежа в базу
							// Возвращать техническую ошибку только в случе, если пополнение происходит вне функции do_payment
							// response(90, "Временная техническая ошибка [3]");
						} else
						{
							$row = mysql_fetch_assoc($sql_result);
							$reg_date = $row['reg_date'];
							$params	= $params.tag_format("reg_id",$reg_id).tag_format("reg_date", date("Y-m-d\TH:i:s",strtotime($reg_date)));	
						}
						response(0, "OK", $params);
					break;
					case 20:
						response(20, "Указанный номер счета отсутствует");
					break;
					case 21:
						response(21, "Запрещены платежи на указанный номер счета");
					break;
					case 22:
						response(22, "Запрещены платежи для указанной услуги");
					break;
					case 23:
						response(23, "Запрещены платежи для указанного агента");
					break;
					case 29:
						response(29, "Неверная сумма платежа");
					break;
					case 90:
						response(90, "Временная техническая ошибка");
					break;
					default:
						response(99, "Неизвестный ответ функции отправки платежа");
				}
			}

		} else response(11, "Указаны не все необходимые параметры");	
		break;
	default:
		response(12, "Неверный формат параметров");
}

	// Логирование
	mysql_query("INSERT INTO logs (date, ip, in_data, out_data, err_code, err_text) VALUES( NOW(), '".$_SERVER["REMOTE_ADDR"]."', '".mysql_escape_string($in_data)."', '".mysql_escape_string($out_data)."', '".$err_code_g."', '".$err_text_g."'  ) ");
?>