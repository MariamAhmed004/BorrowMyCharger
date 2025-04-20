
<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Manage Homeowner Registration';
$view->activePage = 'manage-homeowner-registration';

require_once 'Views/manage-homeowner-registration.phtml';
