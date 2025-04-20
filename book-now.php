<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Book Now';
$view->activePage = 'book-now';

require_once 'Views/book-now.phtml';
