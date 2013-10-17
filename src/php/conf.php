<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);// ^ E_WARNING ^ E_NOTICE ^ E_DEPRECATED);

date_default_timezone_set('Europe/Moscow');

define('ROOT', (isset($_SERVER['HTTP_HOST']) ? ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'] : '') . substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF'])-9));

preg_match('/^([\s\S]+\/)[^\/]+$/', rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/'), $sRa);
if(!defined('SERVER_ROOT'))
	define('SERVER_ROOT', $sRa[1]);
define('DOCUMENT_ROOT', SERVER_ROOT . 'public_html/');
define('SYSTEM_ROOT', SERVER_ROOT . 'sys/');
define('LOGS_ROOT', SERVER_ROOT . 'logs/');

define('COOKIE_NAME', 'geosyst.on-i.ru');

ini_set('max_execution_time', 999999);

switch($_SERVER['HTTP_HOST']){
	case '127.0.0.1':
	case 'localhost':
	case 'getabike.dev':
	default:
		define('DB_INF_HOST', '127.0.0.1');
		define('DB_INF_USER', 'root');
		define('DB_INF_PASSWORD', 'karen');
		define('DB_INF_NAME', 'getabike');
		define('DB_INF_COLLATION', 'utf8');
		break;
	case 'getabike.on-i.ru':
		define('DB_INF_HOST', '127.0.0.1');
		define('DB_INF_USER', 'getabike');
		define('DB_INF_PASSWORD', 'KCWZKFyc9VxEhQaY');
		define('DB_INF_NAME', 'getabike');
		define('DB_INF_COLLATION', 'utf8');
		break;
}


define('ITEMS_ON_PAGE', 5);

function p($var){
	print('<pre>');
	print_r($var);
	print('</pre>');
}

?>