<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Book Charger';
$view->activePage = 'book-charger';

require_once 'Views/book-charger.phtml';
