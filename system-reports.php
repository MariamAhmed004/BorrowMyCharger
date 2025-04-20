
<?php
//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'System Reports';
$view->activePage = 'system-reports';

require_once 'Views/system-reports.phtml';
