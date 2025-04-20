<?php
//blog-posts controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Profile';
$view->activePage = 'profile';

require_once 'Views/profile.phtml';
