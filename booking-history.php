<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Booking History';
$view->activePage = 'booking-history';

require_once 'Views/booking-history.phtml';

