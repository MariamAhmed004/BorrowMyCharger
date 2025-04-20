
<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'View All Profiles';
$view->activePage = 'view-all-profiles';

require_once 'Views/view-all-profiles.phtml';
