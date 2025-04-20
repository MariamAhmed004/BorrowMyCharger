<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Charge Points';
$view->activePage = 'charge-points';

require_once 'Views/charge-points.phtml';

