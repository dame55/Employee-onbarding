<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'admin/login';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['employee'] = 'employee/index';
$route['employee/register'] = 'employee/register';
$route['employee/success'] = 'employee/success';
$route['admin/dashboard'] = 'admin/dashboard';
$route['admin/save_created_employee_from_excel'] = 'admin/save_created_employee_from_excel';
$route['admin/authenticate'] = 'admin/authenticate';
$route['admin/login'] = 'admin/login';
$route['admin/change_password'] = 'admin/change_password';
$route['admin/change_password_success'] = 'admin/change_password_success';
