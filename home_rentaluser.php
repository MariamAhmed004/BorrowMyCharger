<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Home';
$view->activePage = 'home';

require_once 'Views/home_rentaluser.phtml';
