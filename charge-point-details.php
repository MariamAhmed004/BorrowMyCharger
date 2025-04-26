<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Charge Points Details';
$view->activePage = 'charge-points-details';

require_once 'Views/charge-points-details.phtml';

