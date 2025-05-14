<?php
require_once 'Models/systemReports.php';

class SystemReportsController {
    private $systemReportsModel;
    private $view;
    
    public function __construct($view) {
        $this->systemReportsModel = new SystemReportsModel();
        $this->view = $view;
        $this->view->pageTitle = 'System Reports';
        $this->view->activePage = 'system-reports';
    }
    
  public function loadInitialData() {
    // Load summary statistics
    $this->view->bookingStats = $this->systemReportsModel->getBookingStatistics();
    $this->view->userStats = $this->systemReportsModel->getUserStatistics();
    $this->view->availabilityStats = $this->systemReportsModel->getAvailabilityStatistics();
    
    // Load popular charge points
    $this->view->popularChargePoints = $this->view->bookingStats['popularChargePoints'] ?? []; // Safe assignment
    
    // Load filter options
    $this->view->bookingStatuses = $this->systemReportsModel->getBookingStatuses();
    $this->view->userRoles = $this->systemReportsModel->getUserRoles();
    $this->view->userAccountStatuses = $this->systemReportsModel->getUserAccountStatuses();
}
    /**
     * Get bookings based on filters
     * @param array $filterParams Filter parameters
     * @return array Filtered bookings
     */
    public function getFilteredBookings($filterParams = []) {
        if (isset($filterParams['status_id']) && $filterParams['status_id'] > 0) {
            return $this->systemReportsModel->getBookingsByStatus($filterParams['status_id']);
        } else {
            return $this->systemReportsModel->getAllBookings();
        }
    }
    
    /**
     * Get users based on filters
     * @param array $filterParams Filter parameters
     * @return array Filtered users
     */
    public function getFilteredUsers($filterParams = []) {
        if (isset($filterParams['role_id']) && $filterParams['role_id'] > 0) {
            return $this->systemReportsModel->getUsersByRole($filterParams['role_id']);
        } else if (isset($filterParams['status_id']) && $filterParams['status_id'] > 0) {
            return $this->systemReportsModel->getUsersByStatus($filterParams['status_id']);
        } else {
            // Default to showing HomeOwners (role_id = 2) if no filter specified
            return $this->systemReportsModel->getUsersByRole(2);
        }
    }
    
    /**
     * Get all charge points
     * @return array All charge points
     */
    public function getAllChargePoints() {
        return $this->systemReportsModel->getAllChargePoints();
    }
    
    /**
     * Run a custom SQL query (admin only)
     * @param string $sql The SQL query to run
     * @return array Query results
     */
    public function runCustomQuery($sql) {
        return $this->systemReportsModel->runCustomQuery($sql);
    }
    
    /**
     * Process AJAX requests for system reports
     * @param array $requestData The data from the AJAX request
     * @return array Response data
     */
    public function processAjaxRequest($requestData) {
        $response = ['success' => false];
        
        if (isset($requestData['action'])) {
            switch ($requestData['action']) {
                case 'get_bookings':
                    $response['data'] = $this->getFilteredBookings($requestData);
                    $response['success'] = true;
                    break;
                    
                case 'get_users':
                    $response['data'] = $this->getFilteredUsers($requestData);
                    $response['success'] = true;
                    break;
                    
                case 'get_charge_points':
                    $response['data'] = $this->getAllChargePoints();
                    $response['success'] = true;
                    break;
                    
                case 'run_query':
                    if (isset($requestData['sql'])) {
                        $response = $this->runCustomQuery($requestData['sql']);
                    } else {
                        $response['error'] = 'No SQL query provided';
                    }
                    break;
                    
                default:
                    $response['error'] = 'Invalid action specified';
            }
        } else {
            $response['error'] = 'No action specified';
        }
        
        return $response;
    }
}