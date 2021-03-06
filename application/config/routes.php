<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//General:
$route['404_override']          = 'other/404_page_not_found';
$route['translate_uri_dashes']  = FALSE;
$route['default_controller']    = "intents";

//Miner Intents/Entities:
$route['entities/(:num)']       = "entities/en_miner_ui/$1";
$route['entities']              = "entities/en_miner_ui/0";
$route['intents/(:num)']        = "intents/in_miner_ui/$1";
$route['intents']               = "intents/in_miner_ui/0";

$route['(:num)']                = "intents/in_landing_page/$1"; //Public Intent Landing Page

//Users:
$route['login']                 = "messenger/user_login";
$route['logout']                = "messenger/logout";
$route['myaccount']             = "messenger/myaccount";
$route['actionplan']            = "messenger/actionplan/0";
$route['actionplan/(:num)']     = "messenger/actionplan/$1";

//Admin:
$route['platform']              = "admin/platform";
$route['admin']                 = "admin/tools";