<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Borrow Request';
$view->activePage = 'borrow-request';

require_once 'Views/borrow-request.phtml';

