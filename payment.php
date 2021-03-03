<?php

/*
������ ��� ��������� �������� ��������� ��������� ������ 3.5. �������� 5. 19.01.2012
������ ������� 11
��� "����������� �������"
http://bisys.ru/
*/


$agent_id = 1;		// ������������� ������ � ���� ��������
$password = "test1234";	// ������ ��� ��������� ������� ��������
$allowed_ips = "192.168.0.1,127.0.0.1,94.138.149.210,94.138.149.36,89.250.213.20,89.250.213.22,89.250.209.99,94.138.149.32"; // ������ ����������� ������� ��� ��������, ������������� ����� �������
$check_sign = TRUE; // ��������� �� �������

// ��������� ����������� � ��
$db_host = "localhost"; 
$db_user = "root"; 
$db_password = ""; 
$db_name = "bisys";

$in_data = "";		// ������� ������ �������
$out_data = "";		// �������� ������ �������
$err_code_g = "";	// ��� ������
$err_text_g = "";	// ����� ������
$query_sign = "";	// ������� �������

// ���������� ���� �� ������
function get_tag_content($string, $tagname)
{
	$pattern = "|<".$tagname.">(.+)</".$tagname."|si";
	preg_match($pattern, $string, $matches);
	return $matches[1];
}

// ������������ ���� ���� <tagname>value</tagname>
// ���� $newline �������, �� � ������ ����������� ������� �� ����� ������
function tag_format($tagname, $value, $newline = TRUE)
{
	if ($newline)
		return "\n<".$tagname.">".$value."</".$tagname.">"; else
		return "<".$tagname.">".$value."</".$tagname.">"; 
}

// ��������� ������ ������� �� ������
function response(
   $err_code,    // ��� ������
   $err_text,    // ����� ������
   $params = "",    // ���������
   $signed = TRUE    // ����������� �� �����
)
{
   // ��������� �������� ������ � ���������� ����������, ��� ����������� ���������� �� � �����
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
   echo $out_data;        // ������� �����
} 


// �������� ���������� �������
function check_payment(
	$account,	// ����
	$pay_amount,	// �����
	$params,	// ���������
	&$balance	// ������
)
{
	// ��������� ������������� ���������� ������
	
	$sql_result = mysql_query("SELECT * FROM accounts WHERE account = ".$account." AND status = 1");
	if (!$sql_result)
		return 90;

	$row = mysql_fetch_assoc($sql_result);
	$account_id = (int)$row['account_id']; 
	if ($account_id == 0)
		return 20;

	// ��������� $pay_amount � ������ ��������� ��� ��������������

	if ($pay_amount == 0)
		$pay_amount = 10000;

	if (!(($pay_amount >= 100) and ($pay_amount <= 1500000)))
		return 29;

	// ������� ������, ���� ��������
	$balance = $row['balance'];
	return 0;
}

// ���������� �������
function do_payment(
	$account,	// ����
	$pay_amount,	// �����
	$pay_id,	// ���������� ����� �������
	$pay_date,	// ���� �������
	$params,	// ���������
	&$account_id	// ID ����� � ����
)
{
	// ��������� ������������� ���������� ������
	
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

	// �������� ������, ������� 0 � ������ ������, 90 � ������ ������
	// [!] ����� ���������� �������� ���� ��� ���������� ������� ���������� �����


	return 0;
}

// �������� ����������� IP ��� �������
$ips = explode(",",$allowed_ips);
if (array_search($_SERVER["REMOTE_ADDR"],$ips) === FALSE)
{
	response(10, "������ �������� � �������������� ������", "", FALSE);
	exit;
};

if (!isset($_POST['params']))
{
	response(11, "������� �� ��� ����������� ���������", "", FALSE);
	exit;
}

$query = $_POST['params'];
$in_data = $query;
$params = get_tag_content($query, "params");
$query_sign = get_tag_content($query, "sign");
$sign = md5($params.$password);

// �������� �������
if ($check_sign and (($query_sign == "") or (strtoupper($sign) <> strtoupper($query_sign))))
{
	response(13, "�������� �������� �������", "", FALSE);
	exit;
}

$act = (int)get_tag_content($params, "act");
if (($act == "") or ($act == 0))
{
	response(11, "������� �� ��� ����������� ���������", "");
	exit;
}

// �������� ������� � ����

$link = mysql_connect($db_host,$db_user,$db_password);
if (!$link)
{
	response(90, "��������� ����������� ������ [1]");
	exit;
}

$db_selected = mysql_select_db($db_name);
if (!$db_selected)
{
	response(90, "��������� ����������� ������ [2]");
	exit;
}

mysql_query("SET NAMES 'cp1251'");
mysql_query("SET CHARACTER SET 'cp1251'");

switch ($act)
{
	// �������� ���������� �������
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
					response(20, "��������� ����� ����� �����������");
				break;
				case 21:
					response(21, "��������� ������� �� ��������� ����� �����");
				break;
				case 22:
					response(22, "��������� ������� ��� ��������� ������");
				break;
				case 23:
					response(23, "��������� ������� ��� ���������� ������");
				break;
				case 29:
					response(29, "�������� ����� �������");
				break;
				case 90:
					response(90, "��������� ����������� ������");
				break;
				default:
					response(99, "����������� ����� ������� �������� ���������� �������");
			}
		} else response(11, "������� �� ��� ����������� ���������");	
		break;
	// ���������� �������
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
				response(11, "������� �� ��� ����������� ���������");
				exit;
			}
			
			$sql_result = mysql_query("SELECT payments.*, accounts.account FROM payments, accounts WHERE payments.account_id = accounts.account_id AND accounts.status = 1 AND agent_id = ".$agent_id." AND pay_num = '".$pay_id."'");
			if (!$sql_result)
			{
				response(90, "��������� ����������� ������");
				exit;
			}

			$row = mysql_fetch_assoc($sql_result);
			$reg_id = (int)$row['payment_id']; 
	
			// �������� �� ������������
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
					response(1, "������ ��� ��� ��������", $params);
				} else
				{
					response(30, "��� ������ ������ � ��������� �������", $params);
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
							// ������ ��� ���������� ������� � ����
							// ���������� ����������� ������ ������ � �����, ���� ���������� ���������� ��� ������� do_payment
							// response(90, "��������� ����������� ������ [3]");
						} else
						{
							$row = mysql_fetch_assoc($sql_result);
							$reg_date = $row['reg_date'];
							$params	= $params.tag_format("reg_id",$reg_id).tag_format("reg_date", date("Y-m-d\TH:i:s",strtotime($reg_date)));	
						}
						response(0, "OK", $params);
					break;
					case 20:
						response(20, "��������� ����� ����� �����������");
					break;
					case 21:
						response(21, "��������� ������� �� ��������� ����� �����");
					break;
					case 22:
						response(22, "��������� ������� ��� ��������� ������");
					break;
					case 23:
						response(23, "��������� ������� ��� ���������� ������");
					break;
					case 29:
						response(29, "�������� ����� �������");
					break;
					case 90:
						response(90, "��������� ����������� ������");
					break;
					default:
						response(99, "����������� ����� ������� �������� �������");
				}
			}

		} else response(11, "������� �� ��� ����������� ���������");	
		break;
	default:
		response(12, "�������� ������ ����������");
}

	// �����������
	mysql_query("INSERT INTO logs (date, ip, in_data, out_data, err_code, err_text) VALUES( NOW(), '".$_SERVER["REMOTE_ADDR"]."', '".mysql_escape_string($in_data)."', '".mysql_escape_string($out_data)."', '".$err_code_g."', '".$err_text_g."'  ) ");
?>