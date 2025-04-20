<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Dashboard';
$view->activePage = 'dashboard';

require_once 'Views/dashboard_admin.phtml';
