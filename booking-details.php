<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Booking Details';
$view->activePage = 'booknigDetails';

require_once 'Views/booking-details.phtml';
