<?php
defined('VG_ACCESS') or die('Access denied');

//Template
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view';

//Safety
const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = '';
const COOKIE_TIME = 60;
const BLOCK_TIME = 3;

//Pagination
const QTY = 8;
const QTY_LINKS = 3;

//Path Scripts
const ADMIN_CSS_JS = [
	'styles' => [],
	'scripts' => []
];

const USER_CSS_JS = [
	'styles' => [
		'css/style.css',
	],
	'scripts' => [
		'js/script.css',
	]
];

use core\base\exceptions\RouteException;

function autoloadMainClasses($class_name) {
	$class_name = str_replace('\\', '/', $class_name);
	
	if(!@include_once $class_name.'.php') {
		throw new RouteException('Not corrected filename on include - '.$class_name.'.php');
	}
}

spl_autoload_register('autoloadMainClasses');