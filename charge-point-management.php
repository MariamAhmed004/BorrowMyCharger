
<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Charge Point Management';
$view->activePage = 'charge-point-management';

require_once 'Views/charge-point-management.phtml';
