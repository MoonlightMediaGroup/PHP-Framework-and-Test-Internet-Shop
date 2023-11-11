<?php
/* ------------------ */
/* Created by Squizee */
/* ------------------ */

//Vision Ground Constant
define('VG_ACCESS', true);

//Basic Settings and Configs
header('Content-Type:text/html;charset=utf-8');
session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';


//Router and Exception (Entry Point)
use core\base\exceptions\DataBaseExceptions;
use core\base\exceptions\RouteException;
use core\base\controller\RouteController;

try {
	RouteController::instance()->route();
} catch (RouteException $e) {
	exit($e->getMessage());
} catch (DataBaseExceptions $e) {
    exit($e->getMessage());
}