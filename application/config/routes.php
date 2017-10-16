<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/* ******************************
 * Front
 ****************************** */

$route['default_controller'] 		= "front"; // index() Landing page
$route['terms'] 					= "front/terms";
$route['start'] 					= "front/instructors"; //TODO remove later
$route['launch'] 					= "front/instructors";
$route['contact'] 					= "front/contact";
$route['faq'] 					    = "front/faq";
$route['ses'] 						= "front/ses"; //Raw session logs
$route['login']						= "front/login"; //Bootcamp Operator login


//Three steps of the signup process:
$route['bootcamps/(:any)/(:num)/apply'] 	= "front/bootcamp_apply/$1/$2"; //Collects only Email, first & last name
$route['application_status'] 	            = "front/application_status"; //Everything to do with their application.
$route['bootcamps/(:any)']	                = "front/bootcamp_load/$1"; //Landing page
$route['bootcamps'] 				        = "front/bootcamps_browse"; //Browse page

/* ******************************
 * Console for Operators
 ****************************** */

//Admin Guides:
$route['console/help/status_bible'] 			       = "console/status_bible";

$route['console/account'] 						       = "console/account";
$route['console/(:num)/curriculum/(:num)'] 		       = "console/curriculum/$1/$2";
$route['console/(:num)/curriculum'] 			       = "console/curriculum/$1";
$route['console/(:num)/students'] 				       = "console/students/$1";
$route['console/(:num)/stream'] 				       = "console/stream/$1";
$route['console/(:num)/settings'] 				       = "console/settings/$1";
$route['console/(:num)/cohorts/(:num)/scheduler']      = "console/scheduler/$1/$2"; //iFrame view
$route['console/(:num)/cohorts/(:num)'] 		       = "console/cohort/$1/$2";
$route['console/(:num)/cohorts'] 				       = "console/all_cohorts/$1";
$route['console/(:num)/raw'] 				           = "console/raw/$1"; //For dev purposes
$route['console/(:num)'] 			                   = "console/dashboard/$1";
$route['console'] 								       = "console/all_bootcamps";


