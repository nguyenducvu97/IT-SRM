<?php



// Enable error logging but disable display for JSON responses



error_reporting(E_ALL);



ini_set('display_errors', 0);  // Disable errors for JSON responses



ini_set('log_errors', 1);



ini_set('error_log', __DIR__ . '/../logs/api_errors.log');







header("Content-Type: application/json; charset=UTF-8");



header("Access-Control-Allow-Origin: http://localhost");



header("Access-Control-Allow-Credentials: true");



header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");



header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");







// Handle preflight OPTIONS request



if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {



    http_response_code(200);



    exit();



}







require_once __DIR__ . '/../config/session.php';

require_once __DIR__ . '/../config/database.php';

// Temporarily comment out optimization files to fix 500 error

// require_once __DIR__ . '/../config/async_email.php';

// require_once __DIR__ . '/../config/optimized_notifications.php';

// require_once __DIR__ . '/../config/optimized_file_upload.php';

// require_once __DIR__ . '/../config/database_optimizer.php';



require_once __DIR__ . '/../lib/EmailHelper.php'; // Use original EmailHelper

require_once __DIR__ . '/../lib/PHPMailerEmailHelper.php'; // Use PHPMailerEmailHelper for beautiful emails

require_once __DIR__ . '/../lib/NotificationHelper.php'; // Advanced Notification Helper

require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php'; // Role-based Service Request Notifications







// Start session for authentication



startSession();







// Helper function for JSON responses (avoid conflict with database.php)



function serviceJsonResponse($success, $message, $data = null) {



    header('Content-Type: application/json');



    $response = [



        'success' => $success,



        'message' => $message



    ];



    



    if ($data !== null) {



        $response['data'] = $data;



    }



    



    echo json_encode($response);



    exit();



}







if (!isLoggedIn()) {



    serviceJsonResponse(false, "Unauthorized access");



    exit();



}







$database = new Database();



$db = $database->getConnection();







if ($db === null) {



    http_response_code(500);



    echo json_encode([



        'success' => false,



        'message' => 'Database connection failed'



    ]);



    exit();



}







// Debug logging



error_log("=== API REQUEST DEBUG ===");



error_log("Method: " . $_SERVER['REQUEST_METHOD']);



error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));



error_log("POST data: " . json_encode($_POST));



error_log("FILES data: " . json_encode(array_keys($_FILES ?? [])));



error_log("Raw input: " . file_get_contents('php://input'));



error_log("========================");







$method = $_SERVER['REQUEST_METHOD'];







// Ensure session is started with proper cookie settings



$user_id = getCurrentUserId();



$user_role = getCurrentUserRole();



// Debug logging for authentication

error_log("=== AUTH DEBUG ===");

error_log("Session ID: " . session_id());

error_log("User ID: " . ($user_id ?? 'null'));

error_log("User Role: " . ($user_role ?? 'null'));

error_log("Session data: " . json_encode($_SESSION));

error_log("=================");







if ($method == 'GET') {



    $action = isset($_GET['action']) ? $_GET['action'] : '';



    



    if ($action == 'list') {



        $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);



        // For dashboard stats (no filters), use high limit to get all requests

        $has_filters = isset($_GET['status']) || isset($_GET['priority']) || isset($_GET['category']) || isset($_GET['category_id']) || isset($_GET['search']);

        

        if ($has_filters) {

            $limit = max(1, isset($_GET['limit']) ? (int)$_GET['limit'] : 10);

        } else {

            // Dashboard stats - get all requests without limit

            $limit = 10000; // High enough to get all requests

        }



        $offset = ($page - 1) * $limit;



        



        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';



        $priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';



        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';



        $category_id_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

        $search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

        
        $where_clause = "WHERE 1=1";




        $params = [];



        



        if ($user_role != 'admin' && $user_role != 'staff') {



            $where_clause .= " AND sr.user_id = :user_id";



            $params[':user_id'] = $user_id;



        }



        



        if (!empty($status_filter)) {



            $where_clause .= " AND sr.status = :status";



            $params[':status'] = $status_filter;



        }



        



        if (!empty($priority_filter)) {



            $where_clause .= " AND sr.priority = :priority";



            $params[':priority'] = $priority_filter;



        }



        



        if (!empty($category_filter)) {



            $where_clause .= " AND sr.category_id = :category";



            $params[':category'] = $category_filter;



        }



        



        if (!empty($category_id_filter)) {



            $where_clause .= " AND sr.category_id = :category_id";



            $params[':category_id'] = $category_id_filter;



        }



        



        // Add search condition if provided

        if (!empty($search_filter)) {

            $where_clause .= " AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search)";

            $params[':search'] = '%' . $search_filter . '%';

        }



        // Build the base query

        $query = "SELECT sr.*, u.username as requester_name, c.name as category_name,

                  req.id as reject_request_id, req.status as reject_status,

                  sreq.id as support_request_id, sreq.status as support_status,

                  rf.rating as feedback_rating

                  FROM service_requests sr 

                  LEFT JOIN users u ON sr.user_id = u.id 

                  LEFT JOIN categories c ON sr.category_id = c.id 

                  LEFT JOIN reject_requests req ON sr.id = req.service_request_id

                  LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id

                  LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id

                  $where_clause 

                  ORDER BY sr.created_at DESC";

        

        // Special handling for dashboard stats - don't apply limit for total count

        $is_dashboard_stats = !$has_filters; // True when no filters are applied

        

        if (!$is_dashboard_stats) {

            // Apply limit only for non-dashboard requests

            $query .= " LIMIT :limit OFFSET :offset";

        }



        



        $stmt = $db->prepare($query);



        



        foreach ($params as $key => $value) {



            $stmt->bindValue($key, $value);



        }

        

        // Only bind limit/offset for non-dashboard requests

        if (!$is_dashboard_stats) {

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        }



        



        if ($stmt->execute()) {



            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);



            



            $count_query = "SELECT COUNT(*) as total FROM service_requests sr $where_clause";



            $count_stmt = $db->prepare($count_query);



            



            foreach ($params as $key => $value) {



                $count_stmt->bindValue($key, $value);



            }



            $count_stmt->execute();



            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];



            



            // Get status counts



            $status_counts_array = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0, 'request_support' => 0, 'closed' => 0];



            



            // Calculate actual status counts



            $status_query = "SELECT status, COUNT(*) as count FROM service_requests";



            



            // Only filter by user for non-admin/non-staff



            if ($user_role != 'admin' && $user_role != 'staff') {



                $status_query .= " WHERE user_id = :user_id";



            }



            $status_query .= " GROUP BY status";



            



            $status_stmt = $db->prepare($status_query);



            if ($user_role != 'admin' && $user_role != 'staff') {



                $status_stmt->bindParam(":user_id", $user_id);



            }



            $status_stmt->execute();



            



            $status_results = $status_stmt->fetchAll(PDO::FETCH_ASSOC);



            



            foreach ($status_results as $result) {



                $status_counts_array[$result['status']] = $result['count'];



            }



            



            serviceJsonResponse(true, "Service requests retrieved", [



                'requests' => $requests,



                'pagination' => [



                    'page' => $page,



                    'limit' => $limit,



                    'total' => $total,



                    'total_pages' => ceil($total / $limit)



                ],



                'status_counts' => $status_counts_array



            ]);



        } else {



            serviceJsonResponse(false, "Failed to retrieve service requests");



        }



    }



    



    elseif ($action == 'category_stats') {



        // Get request counts by category with status breakdown



        $query = "SELECT c.id as category_id, c.name, 



                         COUNT(sr.id) as total_count,



                         SUM(CASE WHEN sr.status = 'open' THEN 1 ELSE 0 END) as open_count,



                         SUM(CASE WHEN sr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,



                         SUM(CASE WHEN sr.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,



                         SUM(CASE WHEN sr.status = 'closed' THEN 1 ELSE 0 END) as closed_count



                  FROM categories c 



                  LEFT JOIN service_requests sr ON c.id = sr.category_id";



        



        // Add filtering based on user role



        if ($user_role != 'admin' && $user_role != 'staff') {



            $query .= " WHERE sr.user_id = :user_id";



        }



        



        $query .= " GROUP BY c.id, c.name ORDER BY c.name";



        



        $stmt = $db->prepare($query);



        



        if ($user_role != 'admin' && $user_role != 'staff') {



            $stmt->bindParam(':user_id', $_SESSION['user_id']);



        }



        



        if ($stmt->execute()) {



            $stats = [];



            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {



                $stats[$row['category_id']] = [



                    'total' => (int)$row['total_count'],



                    'open' => (int)$row['open_count'],



                    'in_progress' => (int)$row['in_progress_count'],



                    'resolved' => (int)$row['resolved_count'],



                    'closed' => (int)$row['closed_count']



                ];



            }



            



            serviceJsonResponse(true, "Category statistics retrieved successfully", $stats);



        } else {



            serviceJsonResponse(false, "Failed to retrieve category statistics");



        }



    }



    



    elseif ($action == 'get') {



        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;



        



        if ($id <= 0) {



            serviceJsonResponse(false, "Invalid request ID");



        }



        



        $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                        u.email as requester_email, u.phone as requester_phone,
                        assigned.full_name as assigned_name, assigned.email as assigned_email, sr.assigned_at, sr.accepted_at,

                        sreq.id as support_request_id, sreq.support_type, sreq.support_details, 

                        sreq.support_reason, sreq.status as support_status, sreq.admin_reason,

                        sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,

                        sreq_admin.full_name as support_admin_name,

                        rf.rating as feedback_rating, rf.feedback as feedback_text, 

                        rf.software_feedback, rf.ease_of_use, rf.speed_stability, 

                        rf.requirement_meeting, rf.would_recommend, rf.created_at as feedback_created_at,

                        sr.error_description as resolution_error_description,

                        sr.error_type as resolution_error_type, sr.replacement_materials as resolution_replacement_materials,

                        sr.solution_method as resolution_solution_method, 

                        sr.resolved_at as resolution_resolved_at, assigned.full_name as resolver_name,

                        res.resolved_by as resolution_resolved_by, res.error_description as res_error_description,

                        res.error_type as res_error_type, res.replacement_materials as res_replacement_materials,

                        res.solution_method as res_solution_method, res.resolved_at as res_resolved_at,

                        resolver.full_name as resolution_resolver_name

                 FROM service_requests sr

                 LEFT JOIN categories c ON sr.category_id = c.id

                 LEFT JOIN users u ON sr.user_id = u.id

                 LEFT JOIN users assigned ON sr.assigned_to = assigned.id

                 LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id

                 LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id

                 LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id

                 LEFT JOIN resolutions res ON sr.id = res.service_request_id

                 LEFT JOIN users resolver ON res.resolved_by = resolver.id



                 WHERE sr.id = :id";



        



        $stmt = $db->prepare($query);



        $stmt->bindParam(":id", $id);



        $stmt->execute();



        



        if ($stmt->rowCount() > 0) {



            $request = $stmt->fetch(PDO::FETCH_ASSOC);



            



            if ($user_role != 'admin' && $user_role != 'staff' && 



                $request['user_id'] != $user_id) {



                serviceJsonResponse(false, "Access denied");



            }



            



            // Get attachments for this request



            try {



                $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 



                                     FROM attachments 



                                     WHERE service_request_id = :id 



                                     ORDER BY uploaded_at ASC";



                $attachments_stmt = $db->prepare($attachments_query);



                $attachments_stmt->bindParam(":id", $id);



                $attachments_stmt->execute();



                



                $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);



                $request['attachments'] = $attachments;



                // Get resolution attachments if request is resolved



                if ($request['status'] === 'resolved') {



                    $resolution_attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 



                                                   FROM complete_request_attachments 



                                                   WHERE service_request_id = :id 



                                                   ORDER BY uploaded_at ASC";



                    $resolution_attachments_stmt = $db->prepare($resolution_attachments_query);



                    $resolution_attachments_stmt->bindParam(":id", $id);



                    $resolution_attachments_stmt->execute();



                    



                    $resolution_attachments = $resolution_attachments_stmt->fetchAll(PDO::FETCH_ASSOC);



                    $request['resolution_attachments'] = $resolution_attachments;



                } else {



                    $request['resolution_attachments'] = [];



                }



            } catch (Exception $e) {



                $request['attachments'] = [];



            }



            



            // Get comments for this request



            try {



                $comments_query = "SELECT c.*, u.full_name as user_name



                                  FROM comments c



                                  LEFT JOIN users u ON c.user_id = u.id



                                  WHERE c.service_request_id = :id



                                  ORDER BY c.created_at ASC";



                



                $comments_stmt = $db->prepare($comments_query);



                $comments_stmt->bindParam(":id", $id);



                $comments_stmt->execute();



                



                $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);



                $request['comments'] = $comments;



            } catch (Exception $e) {



                $request['comments'] = [];



            }



            



            // Get reject request data for this request



            try {



                $reject_query = "SELECT rr.*, 



                                       u.full_name as requester_name,



                                       admin.full_name as admin_name



                                FROM reject_requests rr



                                LEFT JOIN users u ON rr.rejected_by = u.id



                                LEFT JOIN users admin ON rr.processed_by = admin.id



                                WHERE rr.service_request_id = :id



                                ORDER BY rr.created_at DESC



                                LIMIT 1";



                



                $reject_stmt = $db->prepare($reject_query);



                $reject_stmt->bindParam(":id", $id);



                $reject_stmt->execute();



                



                $reject_request = $reject_stmt->fetch(PDO::FETCH_ASSOC);



                $request['reject_request'] = $reject_request ?: null;



                



                // Get attachments for this reject request



                if ($request['reject_request']) {



                    try {



                        $reject_attachments_query = "SELECT id, filename, original_name, file_size, mime_type, created_at 

                                                     FROM reject_request_attachments 

                                                     WHERE reject_request_id = :id 

                                                     ORDER BY created_at ASC";



                        $reject_attachments_stmt = $db->prepare($reject_attachments_query);



                        $reject_attachments_stmt->bindParam(":id", $request['reject_request']['id']);



                        $reject_attachments_stmt->execute();



                        



                        $all_reject_attachments = $reject_attachments_stmt->fetchAll(PDO::FETCH_ASSOC);

                        

                        // Filter duplicates by original name (same logic as reject_requests.php)

                        $reject_attachments = [];

                        $seen_original_names = [];

                        foreach ($all_reject_attachments as $attachment) {

                            $original_name = $attachment['original_name'];
                            if (!in_array($original_name, $seen_original_names)) {

                                $reject_attachments[] = $attachment;

                                $seen_original_names[] = $original_name;

                            }

                        }

                        

                        $request['reject_request']['attachments'] = $reject_attachments;



                    } catch (Exception $e) {



                        $request['reject_request']['attachments'] = [];



                    }



                }



                



                // Filter sensitive information based on user role

                // Note: Allow all users to see resolution data when request is resolved



            } catch (Exception $e) {



                $request['reject_request'] = null;



            }



            



            // Format support request data if exists



            if ($request['support_request_id']) {



                $request['support_request'] = [



                    'id' => $request['support_request_id'],



                    'support_type' => $request['support_type'],



                    'support_details' => $request['support_details'],



                    'support_reason' => $request['support_reason'],



                    'status' => $request['support_status'],



                    'admin_reason' => $request['admin_reason'],



                    'processed_by' => $request['processed_by'],



                    'processed_at' => $request['processed_at'],



                    'created_at' => $request['support_created_at'],



                    'admin_name' => $request['support_admin_name']



                ];



                



                // Get attachments for this support request



                try {



                    $support_attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 



                                                 FROM support_request_attachments 



                                                 WHERE support_request_id = :id 



                                                 ORDER BY uploaded_at ASC";



                    $support_attachments_stmt = $db->prepare($support_attachments_query);



                    $support_attachments_stmt->bindParam(":id", $request['support_request_id']);



                    $support_attachments_stmt->execute();



                    



                    $support_attachments = $support_attachments_stmt->fetchAll(PDO::FETCH_ASSOC);



                    $request['support_request']['attachments'] = $support_attachments;



                } catch (Exception $e) {



                    $request['support_request']['attachments'] = [];



                }



                



                // Filter sensitive information based on user role



                if ($user_role === 'user') {



                    unset($request['support_request']['admin_reason']);



                    unset($request['support_request']['processed_by']);



                    unset($request['support_request']['processed_at']);



                    unset($request['support_request']['admin_name']);



                }



                



                // Clean up the original fields



                unset($request['support_request_id'], $request['support_type'], 



                      $request['support_details'], $request['support_reason'],



                      $request['support_status'], $request['admin_reason'],



                      $request['processed_by'], $request['processed_at'],



                      $request['support_created_at'], $request['support_admin_name']);



            } else {



                $request['support_request'] = null;



            }



            // Format resolution data if exists

            if ($request['status'] === 'resolved' && $request['resolution_resolver_name']) {

                $request['resolution'] = [

                    'resolver_name' => $request['resolution_resolver_name'],

                    'error_description' => $request['res_error_description'],

                    'error_type' => $request['res_error_type'],

                    'replacement_materials' => $request['res_replacement_materials'],

                    'solution_method' => $request['res_solution_method'],

                    'resolved_at' => $request['res_resolved_at']

                ];

                // Clean up the original resolution fields



                unset($request['resolution_id'], $request['resolution_error_description'],



                      $request['resolution_error_type'], $request['resolution_replacement_materials'],



                      $request['resolution_solution_method'], $request['resolution_resolved_by'],



                      $request['resolution_resolved_at'], $request['resolver_name']);



            } else {



                $request['resolution'] = null;



            }



            



            serviceJsonResponse(true, "Service request retrieved", $request);



        } else {



            serviceJsonResponse(false, "Service request not found");



        }



    }



}







elseif ($method == 'POST') {



    // Check if this is FormData (file upload) or JSON



    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';



    



    // Get action first



    if (strpos($content_type, 'multipart/form-data') !== false) {



        // Handle FormData (file upload)



        $action = isset($_POST['action']) ? $_POST['action'] : '';



    } else {



        // Handle JSON



        $input = json_decode(file_get_contents('php://input'), true);



        $action = isset($input['action']) ? $input['action'] : '';
    }
    
    // OPTIMIZED: Early return for create action to improve performance
    if ($action === 'create') {
        
        if (strpos($content_type, 'multipart/form-data') !== false) {
            // Handle FormData create
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
        } else {
            // Handle JSON create
            $title = isset($input['title']) ? trim($input['title']) : '';
            $description = isset($input['description']) ? trim($input['description']) : '';
            $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
            $priority = isset($input['priority']) ? $input['priority'] : 'medium';
        }
        
        // Validation
        if (empty($title) || empty($description) || $category_id <= 0) {
            serviceJsonResponse(false, "Title, description, and category are required");
        }
        
        try {
            // Direct insert - no category caching, no complex processing
            $query = "INSERT INTO service_requests 
                      (user_id, category_id, title, description, priority, status, created_at, updated_at)
                      VALUES (:user_id, :category_id, :title, :description, :priority, 'open', NOW(), NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":priority", $priority);
            
            if ($stmt->execute()) {
                $request_id = $db->lastInsertId();
                
                // Handle file attachments if any (optimized)
                $uploaded_files = [];
                
                if (strpos($content_type, 'multipart/form-data') !== false && isset($_FILES['attachments'])) {
                    
                    $uploads_dir = '../uploads/attachments/' . $request_id . '/';
                    if (!file_exists($uploads_dir)) {
                        mkdir($uploads_dir, 0755, true);
                    }
                    
                    $file_attachments = $_FILES['attachments'];
                    if (is_array($file_attachments['name'])) {
                        $file_count = count($file_attachments['name']);
                        
                        for ($i = 0; $i < $file_count; $i++) {
                            if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {
                                $original_name = $file_attachments['name'][$i];
                                $file_size = $file_attachments['size'][$i];
                                $file_tmp = $file_attachments['tmp_name'][$i];
                                $file_type = $file_attachments['type'][$i];
                                
                                // Quick validation
                                $max_size = 10 * 1024 * 1024; // 10MB
                                if ($file_size > $max_size) {
                                    continue;
                                }
                                
                                // Generate unique filename
                                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                                $unique_filename = uniqid('req_', true) . '.' . $file_extension;
                                $file_path = $uploads_dir . $unique_filename;
                                
                                // Move file - handle both uploaded files and temp files
                                if (move_uploaded_file($file_tmp, $file_path) || copy($file_tmp, $file_path)) {
                                    // Insert attachment record
                                    $attach_query = "INSERT INTO attachments 
                                                   (service_request_id, original_name, filename, file_size, mime_type, uploaded_by, uploaded_at)
                                                   VALUES (:service_request_id, :original_name, :filename, :file_size, :mime_type, :uploaded_by, NOW())";
                                    
                                    $attach_stmt = $db->prepare($attach_query);
                                    $attach_stmt->bindParam(":service_request_id", $request_id);
                                    $attach_stmt->bindParam(":original_name", $original_name);
                                    $attach_stmt->bindParam(":filename", $unique_filename);
                                    $attach_stmt->bindParam(":file_size", $file_size);
                                    $attach_stmt->bindParam(":mime_type", $file_type);
                                    $attach_stmt->bindParam(":uploaded_by", $user_id);
                                    
                                    if ($attach_stmt->execute()) {
                                        $uploaded_files[] = [
                                            'original_name' => $original_name,
                                            'filename' => $unique_filename,
                                            'file_size' => $file_size,
                                            'mime_type' => $file_type
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                
                $response_data = [
                    'id' => $request_id,
                    'title' => $title,
                    'description' => $description,
                    'category_id' => $category_id,
                    'priority' => $priority,
                    'status' => 'open',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Debug info
                $response_data['debug'] = [
                    'content_type' => $content_type,
                    'is_multipart' => strpos($content_type, 'multipart/form-data') !== false,
                    'files_set' => isset($_FILES['attachments']),
                    'files_data' => isset($_FILES['attachments']) ? 'Array(' . count($_FILES['attachments']['name']) . ' files)' : 'No files',
                    'uploaded_files_count' => count($uploaded_files),
                    'file_details' => isset($_FILES['attachments']) ? [
                        'names' => $_FILES['attachments']['name'],
                        'errors' => $_FILES['attachments']['error'],
                        'sizes' => $_FILES['attachments']['size'],
                        'tmp_names' => $_FILES['attachments']['tmp_name']
                    ] : []
                ];
                
                if (!empty($uploaded_files)) {
                    $response_data['uploaded_files'] = $uploaded_files;
                    $response_data['message'] = 'Request created successfully with ' . count($uploaded_files) . ' file(s)';
                } else {
                    $response_data['message'] = 'Request created successfully';
                }
                
                // OPTIMIZED: Background notifications for staff and admin
                register_shutdown_function(function() use ($request_id, $title, $user_id, $category_id, $db) {
                    ignore_user_abort(true);
                    set_time_limit(300); // 5 minutes for background processing
                    
                    try {
                        require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php';
                        $notificationHelper = new ServiceRequestNotificationHelper();
                        
                        // Get user details for notifications
                        $user_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                        $user_stmt->execute([$user_id]);
                        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        $requester_name = $user_data['full_name'] ?? 'Unknown User';
                        
                        // Get category name
                        $cat_stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
                        $cat_stmt->execute([$category_id]);
                        $cat_data = $cat_stmt->fetch(PDO::FETCH_ASSOC);
                        $category_name = $cat_data['name'] ?? 'Unknown';
                        
                        // Notify staff
                        $notificationHelper->notifyStaffNewRequest(
                            $request_id, 
                            $title, 
                            $requester_name, 
                            $category_name
                        );
                        
                        // Notify admin
                        $notificationHelper->notifyAdminNewRequest(
                            $request_id, 
                            $title, 
                            $requester_name, 
                            $category_name
                        );
                        
                    } catch (Exception $e) {
                        error_log("Background notification failed: " . $e->getMessage());
                    }
                });
                
                serviceJsonResponse(true, $response_data['message'], $response_data);
            } else {
                serviceJsonResponse(false, "Failed to create request");
            }
        } catch (Exception $e) {
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    // Handle different actions

    elseif ($action === 'resolve') {

        

        error_log("Resolve action detected - Content-Type: " . $content_type);

        error_log("Is FormData: " . (strpos($content_type, 'multipart/form-data') !== false ? 'Yes' : 'No'));

        

        // Handle resolve action with FormData

        if (strpos($content_type, 'multipart/form-data') !== false) {

            error_log("Processing FormData resolve");

            // Handle FormData (file upload)

            $request_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            $error_description = isset($_POST['error_description']) ? trim($_POST['error_description']) : '';

            $error_type = isset($_POST['error_type']) ? trim($_POST['error_type']) : '';

            $replacement_materials = isset($_POST['replacement_materials']) ? trim($_POST['replacement_materials']) : '';

            $solution_method = isset($_POST['solution_method']) ? trim($_POST['solution_method']) : '';

            

            error_log("Resolve data - ID: $request_id, Error: $error_description");

            

            // Handle file uploads

            $attachments = [];

            if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {

                $file_count = count($_FILES['attachments']['name']);

                error_log("Found $file_count files to upload");

                for ($i = 0; $i < $file_count; $i++) {

                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {

                        $attachments[] = [

                            'name' => $_FILES['attachments']['name'][$i],

                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],

                            'type' => $_FILES['attachments']['type'][$i],

                            'size' => $_FILES['attachments']['size'][$i],

                            'error' => $_FILES['attachments']['error'][$i]

                        ];

                    }

                }

            }

            

            // Use the same resolve logic as below

            handleResolveRequest($request_id, $error_description, $error_type, $replacement_materials, $solution_method, $attachments, $user_id, $user_role, $db);

            return; // Prevent duplicate execution

            

        } else {

            error_log("Processing JSON resolve");

            // Handle JSON (existing logic)

            $input = json_decode(file_get_contents('php://input'), true);

            // Continue to existing resolve logic below - don't return here

        }



    } elseif ($action === 'reject_request') {

        

        error_log("Reject request action detected in first POST block");

        

        // Handle reject request with FormData (file upload)

        if (strpos($content_type, 'multipart/form-data') !== false) {

            error_log("Processing FormData reject request");

            // Handle FormData (file upload)

            $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;

            $reject_reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : '';

            $reject_details = isset($_POST['reject_details']) ? trim($_POST['reject_details']) : '';

            

            error_log("Reject data - ID: $request_id, Reason: $reject_reason");

            

            // Validate required fields

            if ($request_id <= 0 || empty($reject_reason)) {

                serviceJsonResponse(false, "Request ID and reject reason are required");

                return;

            }

            

            // Only staff and admin can reject requests

            if ($user_role != 'staff' && $user_role != 'admin') {

                serviceJsonResponse(false, "Access denied - Only staff and admin can reject requests");

                return;

            }

            

            try {

                // Check if request exists

                $check_query = "SELECT id FROM service_requests WHERE id = :request_id";

                $check_stmt = $db->prepare($check_query);

                $check_stmt->bindParam(":request_id", $request_id);

                $check_stmt->execute();

                

                if ($check_stmt->rowCount() == 0) {

                    serviceJsonResponse(false, "Request not found");

                    return;

                }

                

                // Check if user already submitted a reject request for this request

                $duplicate_check_query = "SELECT id, status, reject_reason, reject_details FROM reject_requests 

                                        WHERE service_request_id = :request_id AND rejected_by = :rejected_by

                                        ORDER BY created_at DESC LIMIT 1";

                $duplicate_check_stmt = $db->prepare($duplicate_check_query);

                $duplicate_check_stmt->bindParam(":request_id", $request_id);

                $duplicate_check_stmt->bindParam(":rejected_by", $user_id);

                $duplicate_check_stmt->execute();

                

                if ($duplicate_check_stmt->rowCount() > 0) {

                    $existing_request = $duplicate_check_stmt->fetch(PDO::FETCH_ASSOC);

                    

                    // Allow update if request is still pending (not processed by admin)

                    if ($existing_request['status'] === 'pending') {

                        // Update existing reject request

                        $update_query = "UPDATE reject_requests 

                                       SET reject_reason = :reject_reason, reject_details = :reject_details, updated_at = NOW()

                                       WHERE id = :existing_id";

                        

                        $update_stmt = $db->prepare($update_query);

                        $update_stmt->bindParam(":reject_reason", $reject_reason);

                        $update_stmt->bindParam(":reject_details", $reject_details);

                        $update_stmt->bindParam(":existing_id", $existing_request['id']);

                        

                        if ($update_stmt->execute()) {

                            $reject_id = $existing_request['id'];

                            

                            // Handle file uploads if any (add new files)

                            $uploaded_files = [];

                            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {

                                error_log("Processing file uploads for updated reject request $reject_id");

                                

                                $uploads_dir = __DIR__ . '/../uploads/reject_requests/';

                                if (!file_exists($uploads_dir)) {

                                    mkdir($uploads_dir, 0755, true);

                                }

                                

                                $file_attachments = $_FILES['attachments'];

                                $file_count = count($file_attachments['name']);

                                

                                for ($i = 0; $i < $file_count; $i++) {

                                    if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {

                                        $original_name = $file_attachments['name'][$i];

                                        $file_size = $file_attachments['size'][$i];

                                        $file_tmp = $file_attachments['tmp_name'][$i];

                                        $file_type = $file_attachments['type'][$i];

                                        

                                        // Generate unique filename

                                        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);

                                        $unique_filename = uniqid('reject_', true) . '.' . $file_extension;

                                        $file_path = $uploads_dir . $unique_filename;

                                        

                                        // Validate file (size, type)

                                        $max_size = 10 * 1024 * 1024; // 10MB

                                        $allowed_types = [

                                            'image/jpeg', 'image/png', 'image/gif',

                                            'application/pdf', 'application/msword',

                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                                            'application/vnd.ms-excel',

                                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',

                                            'application/vnd.ms-powerpoint',

                                            'text/plain', 'application/zip'

                                        ];

                                        

                                        if ($file_size > $max_size) {

                                            error_log("File too large: $original_name ($file_size bytes)");

                                            continue;

                                        }

                                        

                                        if (!in_array($file_type, $allowed_types)) {

                                            error_log("File type not allowed: $original_name ($file_type)");

                                            continue;

                                        }

                                        

                                        // Move file

                                        if (move_uploaded_file($file_tmp, $file_path)) {

                                            // Save to database

                                            $attachment_stmt = $db->prepare("

                                                INSERT INTO reject_request_attachments 

                                                (reject_request_id, original_name, filename, file_size, file_type, uploaded_at)

                                                VALUES (:reject_id, :original_name, :filename, :file_size, :file_type, NOW())

                                            ");

                                            

                                            $attachment_stmt->bindParam(":reject_id", $reject_id);

                                            $attachment_stmt->bindParam(":original_name", $original_name);

                                            $attachment_stmt->bindParam(":filename", $unique_filename);

                                            $attachment_stmt->bindParam(":file_size", $file_size);

                                            $attachment_stmt->bindParam(":file_type", $file_type);

                                            

                                            if ($attachment_stmt->execute()) {

                                                $uploaded_files[] = [

                                                    'original_name' => $original_name,

                                                    'filename' => $unique_filename,

                                                    'file_size' => $file_size,

                                                    'file_type' => $file_type

                                                ];

                                            }

                                        }

                                    }

                                }

                            }

                            

                            $response_data = [

                                'success' => true,

                                'message' => 'Reject request updated successfully',

                                'reject_id' => $reject_id,

                                'updated' => true

                            ];

                            

                            if (!empty($uploaded_files)) {

                                $response_data['uploaded_files'] = $uploaded_files;

                                $response_data['message'] .= ' with ' . count($uploaded_files) . ' new file(s) attached';

                            }

                            

                            echo json_encode($response_data);

                            return; // Prevent double response

                        } else {

                            serviceJsonResponse(false, "Failed to update reject request");

                            return; // Prevent double response

                        }

                    } else {

                        // Request already processed by admin, cannot modify

                        serviceJsonResponse(false, "Your reject request has already been processed by admin and cannot be modified");

                        return; // Prevent double response

                    }

                }

                

                // Insert reject request

                $insert_query = "INSERT INTO reject_requests 

                                 (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 

                                 VALUES (:request_id, :rejected_by, :reject_reason, :reject_details, 'pending', NOW())";

                

                $insert_stmt = $db->prepare($insert_query);

                $insert_stmt->bindParam(":request_id", $request_id);

                $insert_stmt->bindParam(":rejected_by", $user_id);

                $insert_stmt->bindParam(":reject_reason", $reject_reason);

                $insert_stmt->bindParam(":reject_details", $reject_details);

                

                if ($insert_stmt->execute()) {

                    $reject_id = $db->lastInsertId();

                    

                    // Send notification to admin about new reject request

                    try {

                        $notificationHelper = new ServiceRequestNotificationHelper();

                        

                        // Get request details for notification

                        $requestDetails = $notificationHelper->getRequestDetails($request_id);

                        

                        // Notify admin about rejection request

                        $notificationHelper->notifyAdminRejectionRequest(

                            $request_id, 

                            $reject_reason . ($reject_details ? " - " . $reject_details : ""), 

                            $_SESSION['full_name'] ?? 'Staff', 

                            $requestDetails['title']

                        );

                        

                    } catch (Exception $e) {

                        error_log("Failed to send reject request notification: " . $e->getMessage());

                        // Continue even if notification fails

                    }

                    

                    // Handle file uploads if any

                    $uploaded_files = [];

                    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {

                        error_log("Processing file uploads for reject request $reject_id");

                        

                        $uploads_dir = __DIR__ . '/../uploads/reject_requests/';

                        if (!file_exists($uploads_dir)) {

                            mkdir($uploads_dir, 0755, true);

                        }

                        

                        $file_attachments = $_FILES['attachments'];

                        $file_count = count($file_attachments['name']);

                        

                        for ($i = 0; $i < $file_count; $i++) {

                            if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {

                                $original_name = $file_attachments['name'][$i];

                                $file_size = $file_attachments['size'][$i];

                                $file_tmp = $file_attachments['tmp_name'][$i];

                                $file_type = $file_attachments['type'][$i];

                                

                                // Generate unique filename

                                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);

                                $unique_filename = uniqid('reject_', true) . '.' . $file_extension;

                                $file_path = $uploads_dir . $unique_filename;

                                

                                // Validate file (size, type)

                                $max_size = 10 * 1024 * 1024; // 10MB

                                $allowed_types = [

                                    'image/jpeg', 'image/png', 'image/gif',

                                    'application/pdf', 'application/msword',

                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                                    'application/vnd.ms-excel',

                                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',

                                    'application/vnd.ms-powerpoint',

                                    'text/plain', 'application/zip'

                                ];

                                

                                if ($file_size > $max_size) {

                                    error_log("File too large: $original_name ($file_size bytes)");

                                    continue;

                                }

                                

                                if (!in_array($file_type, $allowed_types)) {

                                    error_log("File type not allowed: $original_name ($file_type)");

                                    continue;

                                }

                                

                                // Move file

                                if (move_uploaded_file($file_tmp, $file_path)) {

                                    // Save to database

                                    $attachment_stmt = $db->prepare("

                                        INSERT INTO reject_request_attachments 

                                        (reject_request_id, original_name, filename, file_size, mime_type, uploaded_at)

                                        VALUES (:reject_id, :original_name, :filename, :file_size, :mime_type, NOW())

                                    ");

                                    

                                    $attachment_stmt->bindParam(":reject_id", $reject_id);

                                    $attachment_stmt->bindParam(":original_name", $original_name);

                                    $attachment_stmt->bindParam(":filename", $unique_filename);

                                    $attachment_stmt->bindParam(":file_size", $file_size);

                                    $attachment_stmt->bindParam(":mime_type", $file_type);

                                    

                                    if ($attachment_stmt->execute()) {

                                        $uploaded_files[] = [

                                            'original_name' => $original_name,

                                            'filename' => $unique_filename,

                                            'file_size' => $file_size,

                                            'mime_type' => $file_type

                                        ];

                                    }

                                }

                            }

                        }

                    }

                    

                    $response_data = [

                        'success' => true,

                        'message' => 'Reject request submitted successfully',

                        'reject_id' => $reject_id

                    ];

                    

                    if (!empty($uploaded_files)) {

                        $response_data['uploaded_files'] = $uploaded_files;

                        $response_data['message'] .= ' with ' . count($uploaded_files) . ' file(s) attached';

                    }

                    

                    echo json_encode($response_data);

                    return; // Prevent double response

                } else {

                    serviceJsonResponse(false, "Failed to submit reject request");

                    return; // Prevent double response

                }

            } catch (Exception $e) {

                // Check if this is a duplicate key error
                if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    
                    // This means there's already a pending request, try to update it instead
                    try {
                        // Get the existing pending request
                        $existing_query = "SELECT id, status, reject_reason, reject_details FROM reject_requests 
                                         WHERE service_request_id = :request_id AND rejected_by = :rejected_by
                                         ORDER BY created_at DESC LIMIT 1";
                        
                        $existing_stmt = $db->prepare($existing_query);
                        $existing_stmt->bindParam(":request_id", $request_id);
                        $existing_stmt->bindParam(":rejected_by", $user_id);
                        $existing_stmt->execute();
                        
                        if ($existing_stmt->rowCount() > 0) {
                            $existing_request = $existing_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Update existing reject request
                            $update_query = "UPDATE reject_requests 
                                           SET reject_reason = :reject_reason, reject_details = :reject_details, updated_at = NOW()
                                           WHERE id = :existing_id";
                            
                            $update_stmt = $db->prepare($update_query);
                            $update_stmt->bindParam(":reject_reason", $reject_reason);
                            $update_stmt->bindParam(":reject_details", $reject_details);
                            $update_stmt->bindParam(":existing_id", $existing_request['id']);
                            
                            if ($update_stmt->execute()) {
                                $reject_id = $existing_request['id'];
                                
                                // Handle file uploads if any (add new files)
                                $uploaded_files = [];
                                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                                    error_log("Processing file uploads for updated reject request $reject_id");
                                    
                                    $uploads_dir = __DIR__ . '/../uploads/reject_requests/';
                                    if (!file_exists($uploads_dir)) {
                                        mkdir($uploads_dir, 0755, true);
                                    }
                                    
                                    $file_attachments = $_FILES['attachments'];
                                    $file_count = count($file_attachments['name']);
                                    
                                    for ($i = 0; $i < $file_count; $i++) {
                                        if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {
                                            $original_name = $file_attachments['name'][$i];
                                            $file_size = $file_attachments['size'][$i];
                                            $file_tmp = $file_attachments['tmp_name'][$i];
                                            $file_type = $file_attachments['type'][$i];
                                            
                                            // Generate unique filename
                                            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                                            $unique_filename = uniqid('reject_', true) . '.' . $file_extension;
                                            $file_path = $uploads_dir . $unique_filename;
                                            
                                            // Move file
                                            if (move_uploaded_file($file_tmp, $file_path)) {
                                                // Save to database
                                                $attachment_stmt = $db->prepare("
                                                    INSERT INTO reject_request_attachments 
                                                    (reject_request_id, original_name, filename, file_size, mime_type, uploaded_at)
                                                    VALUES (:reject_id, :original_name, :filename, :file_size, :mime_type, NOW())
                                                ");
                                                
                                                $attachment_stmt->bindParam(":reject_id", $reject_id);
                                                $attachment_stmt->bindParam(":original_name", $original_name);
                                                $attachment_stmt->bindParam(":filename", $unique_filename);
                                                $attachment_stmt->bindParam(":file_size", $file_size);
                                                $attachment_stmt->bindParam(":mime_type", $file_type);
                                                
                                                if ($attachment_stmt->execute()) {
                                                    $uploaded_files[] = [
                                                        'original_name' => $original_name,
                                                        'filename' => $unique_filename,
                                                        'file_size' => $file_size,
                                                        'mime_type' => $file_type
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                $response_data = [
                                    'success' => true,
                                    'message' => 'Reject request updated successfully',
                                    'reject_id' => $reject_id,
                                    'updated' => true
                                ];
                                
                                if (!empty($uploaded_files)) {
                                    $response_data['uploaded_files'] = $uploaded_files;
                                    $response_data['message'] .= ' with ' . count($uploaded_files) . ' new file(s) attached';
                                }
                                
                                echo json_encode($response_data);
                                return;
                            }
                        }
                    } catch (Exception $update_e) {
                        error_log("Failed to handle duplicate key: " . $update_e->getMessage());
                    }
                }
                
                // If we get here, it's a genuine error
                serviceJsonResponse(false, "Database error: " . $e->getMessage());
                return; // Prevent double response

            }

        } else {

            serviceJsonResponse(false, "Reject request requires FormData");

            return; // Prevent double response

        }

        

    } else {

        // Handle create request (original logic)



        if (strpos($content_type, 'multipart/form-data') !== false) {



            // Handle FormData (file upload)



            $title = isset($_POST['title']) ? trim($_POST['title']) : '';



            $description = isset($_POST['description']) ? trim($_POST['description']) : '';



            $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;



            $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';



        } else {



            // Handle JSON



            $input = json_decode(file_get_contents('php://input'), true);



            



            if (!$input) {



                serviceJsonResponse(false, "Invalid JSON data");



                return;



            }



            



            $title = isset($input['title']) ? trim($input['title']) : '';



            $description = isset($input['description']) ? trim($input['description']) : '';



            $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;



            $priority = isset($input['priority']) ? $input['priority'] : 'medium';



        }



    



    // Skip validation for reject_request and resolve actions (they have their own validation)

    if ($action !== 'reject_request' && $action !== 'resolve') {

        if (empty($title) || empty($description) || $category_id <= 0) {



            serviceJsonResponse(false, "Title, description, and category are required");



            return;



        }

    }



    



    try {

        // Database connection optimization (original)

        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        

        // Cache category lookup to avoid JOIN in main query (original)

        $category_cache = [];

        $category_stmt = $db->prepare("SELECT id, name FROM categories");

        $category_stmt->execute();

        $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $cat) {

            $category_cache[$cat['id']] = $cat['name'];

        }

        

        // Only execute INSERT for create actions (not for reject_request, resolve, update, etc.)

        if ($action === '' || $action === 'create') {

            // Start timing the request creation

            $request_start = microtime(true);



            $query = "INSERT INTO service_requests 

                      (user_id, category_id, title, description, priority, status, created_at, updated_at) 

                      VALUES (:user_id, :category_id, :title, :description, :priority, 'open', NOW(), NOW())";

            

            $stmt = $db->prepare($query);

            $stmt->bindParam(":user_id", $user_id);

            $stmt->bindParam(":category_id", $category_id);

            $stmt->bindParam(":title", $title);

            $stmt->bindParam(":description", $description);

            $stmt->bindParam(":priority", $priority);

            

            if ($stmt->execute()) {

            $request_id = $db->lastInsertId();



            



            // Get request details without JOIN for better performance (original)

            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email

                              FROM service_requests sr

                              LEFT JOIN users u ON sr.user_id = u.id

                              WHERE sr.id = :request_id";

            $request_stmt = $db->prepare($request_query);

            $request_stmt->bindParam(":request_id", $request_id);

            $request_stmt->execute();

            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

            

            // Get category from cache

            $request_data['category'] = $category_cache[$request_data['category_id']] ?? 'Unknown';



            

            // Map data to template variables

            $email_data = array(

                'id' => $request_data['id'],

                'title' => $request_data['title'],

                'requester_name' => $request_data['requester_name'],

                'category' => $request_data['category'],

                'priority' => $request_data['priority'],

                'description' => $request_data['description']

            );



            



            // Send email notification to staff and admin - ORIGINAL

            $email_start = microtime(true);

            

            try {

                // Quick SMTP connectivity check with shorter timeout

                $smtp_socket = @fsockopen('gw.sgitech.com.vn', 25, $errno, $errstr, 0.5);

                

                if ($smtp_socket) {

                    // SMTP is responsive - send email

                    fclose($smtp_socket);

                    $emailHelper = new EmailHelper();

                    $emailHelper->sendNewRequestNotification($email_data);

                    error_log("Email sent in " . round((microtime(true) - $email_start) * 1000, 2) . "ms");

                } else {

                    // SMTP is down - log and continue quickly

                    error_log("SMTP down - skipping email ({$errno}: {$errstr})");

                }

            } catch (Exception $e) {

                error_log("Email error: " . $e->getMessage());

            }



            // Create role-based notifications for staff and admin

            $notification_start = microtime(true);

            error_log("DEBUG: Starting notification creation for request $request_id");



            try {

                error_log("DEBUG: Creating ServiceRequestNotificationHelper");

                $notificationHelper = new ServiceRequestNotificationHelper();

                

                error_log("DEBUG: Request data for notifications - Title: " . $request_data['title'] . ", Requester: " . $request_data['requester_name'] . ", Category: " . $request_data['category']);

                

                // Notify staff about new request

                error_log("DEBUG: Calling notifyStaffNewRequest");

                $staffResult = $notificationHelper->notifyStaffNewRequest(

                    $request_id, 

                    $request_data['title'], 

                    $request_data['requester_name'], 

                    $request_data['category']

                );

                error_log("DEBUG: Staff notification result: " . ($staffResult ? 'SUCCESS' : 'FAILED'));

                

                // Admin notification already sent in the first code path (line 1966)
                // Remove duplicate to prevent sending 2 notifications to admin

                

                error_log("Role-based notifications created in " . (microtime(true) - $notification_start) . "s");



            } catch (Exception $e) {

                error_log("Failed to create role-based notifications: " . $e->getMessage());

                error_log("Notification exception trace: " . $e->getTraceAsString());

                // Continue even if notification creation fails

            }



            



            // Handle file uploads if any - OPTIMIZED

            $attachment_start = microtime(true);

            $attachment_count = 0;

            

            error_log("DEBUG: Checking files - isset(\$_FILES['attachments']): " . (isset($_FILES['attachments']) ? 'YES' : 'NO'));

            if (isset($_FILES['attachments'])) {

                error_log("DEBUG: FILES data: " . print_r($_FILES['attachments'], true));

            }

            

            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {

                $upload_dir = __DIR__ . '/../uploads/requests/';

                if (!is_dir($upload_dir)) {

                    mkdir($upload_dir, 0777, true);

                }



                $files = $_FILES['attachments'];

                $attachment_data = [];

                

                // Process files first

                foreach ($files['name'] as $key => $name) {

                    if ($files['error'][$key] === UPLOAD_ERR_OK) {

                        $tmp_name = $files['tmp_name'][$key];

                        $file_extension = pathinfo($name, PATHINFO_EXTENSION);

                        $new_filename = uniqid() . '_' . $name;

                        $upload_path = $upload_dir . $new_filename;

                        

                        if (move_uploaded_file($tmp_name, $upload_path)) {

                            $attachment_data[] = [

                                'filename' => $new_filename,

                                'original_name' => $name,

                                'file_size' => $files['size'][$key],

                                'mime_type' => $files['type'][$key]

                            ];

                            $attachment_count++;

                        }

                    }

                }

                

                // Batch insert attachments

                if (!empty($attachment_data)) {

                    $attach_query = "INSERT INTO attachments 

                                   (service_request_id, filename, original_name, file_size, mime_type, uploaded_by, uploaded_at) 

                                   VALUES ";

                    $values = [];

                    $params = [];

                    

                    foreach ($attachment_data as $attachment) {

                        $values[] = "(?, ?, ?, ?, ?, ?, NOW())";

                        $params = array_merge($params, [

                            $request_id, 

                            $attachment['filename'],

                            $attachment['original_name'],

                            $attachment['file_size'],

                            $attachment['mime_type'],

                            $user_id

                        ]);

                    }

                    

                    $attach_query .= implode(',', $values);

                    $attach_stmt = $db->prepare($attach_query);

                    $attach_stmt->execute($params);

                }

                

                error_log("Processed $attachment_count attachments in " . round((microtime(true) - $attachment_start) * 1000, 2) . "ms");

            }



            



            // Log total execution time

            $total_time = round((microtime(true) - $request_start) * 1000, 2);

            error_log("Total request creation time: {$total_time}ms (Request ID: {$request_id})");

            

            serviceJsonResponse(true, "Service request created successfully", ['id' => $request_id]);



        } else {



            serviceJsonResponse(false, "Failed to create service request");



        }



    } else {

        // For other actions (reject_request, resolve, update, etc.), continue to next block

        // This allows the second POST block to handle these actions properly

    }



    } catch (Exception $e) {



        serviceJsonResponse(false, "Database error: " . $e->getMessage());



    }



    } // Close else block for non-reject requests



}







elseif ($method == 'POST') {



    // Handle POST requests (including reject requests with file uploads)



    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';



    



    if (strpos($content_type, 'multipart/form-data') !== false) {



        // Handle FormData (file upload)



        $action = isset($_POST['action']) ? $_POST['action'] : '';



        error_log("POST FormData action: '$action'");



        error_log("POST all data: " . print_r($_POST, true));



    } else {



        // Handle regular form POST or JSON



        $input = json_decode(file_get_contents('php://input'), true);



        



        // If JSON parsing failed, try to use POST data (regular form)



        if ($input === null && !empty($_POST)) {



            $input = $_POST;



        }



        



        $action = isset($input['action']) ? $input['action'] : '';



        error_log("POST JSON/Form action: '$action'");



    }



    



    // CRITICAL DEBUG: Check what's happening



    error_log("=== CRITICAL DEBUG ===");



    error_log("Method: POST");



    error_log("Raw action: '$action'");



    error_log("Trimmed action: '" . trim($action) . "'");



    error_log("Action == 'update': " . ($action == 'update' ? 'YES' : 'NO'));



    error_log("Action == 'reject_request': " . ($action == 'reject_request' ? 'YES' : 'NO'));



    error_log("Trimmed action == 'update': " . (trim($action) == 'update' ? 'YES' : 'NO'));



    error_log("Trimmed action == 'reject_request': " . (trim($action) == 'reject_request' ? 'YES' : 'NO'));



    error_log("=== END CRITICAL DEBUG ===");



    



    // DIRECT DEBUG RESPONSE



    if (isset($_POST['debug_mode']) && $_POST['debug_mode'] === 'true') {



        header('Content-Type: application/json');



        echo json_encode([



            'debug_mode' => true,



            'raw_action' => $action,



            'trimmed_action' => trim($action),



            'post_data' => $_POST,



            'files_data' => $_FILES,



            'comparisons' => [



                'raw_equals_update' => ($action == 'update'),



                'raw_equals_reject' => ($action == 'reject_request'),



                'trimmed_equals_update' => (trim($action) == 'update'),



                'trimmed_equals_reject' => (trim($action) == 'reject_request')



            ]



        ]);



        exit;



    }



    



    // Debug: Log action only for debugging

    error_log("POST action: '$action'");



    



    if (trim($action) == 'update') {



        // Update service request (admin only)



        if ($user_role != 'admin') {



            serviceJsonResponse(false, "Access denied. Admin access required.");



            return;



        }



        



        // Handle both JSON and FormData



        if (strpos($content_type, 'multipart/form-data') !== false) {



            // Handle FormData



            $request_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;



            $title = isset($_POST['title']) ? trim($_POST['title']) : '';



            $description = isset($_POST['description']) ? trim($_POST['description']) : '';



            $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;



            $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';



            $status = isset($_POST['status']) ? $_POST['status'] : 'open';



            $assigned_to = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;



        } else {



            // Handle JSON



            $request_id = isset($input['id']) ? (int)$input['id'] : 0;



            $title = isset($input['title']) ? trim($input['title']) : '';



            $description = isset($input['description']) ? trim($input['description']) : '';



            $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;



            $priority = isset($input['priority']) ? $input['priority'] : 'medium';



            $status = isset($input['status']) ? $input['status'] : 'open';



            $assigned_to = isset($input['assigned_to']) ? (int)$input['assigned_to'] : null;



        }



        



        if ($request_id <= 0 || empty($title) || empty($description) || $category_id <= 0) {



            serviceJsonResponse(false, "Request ID, title, description, and category are required");



            return;



        }



        



        try {



            // Check if request exists



            $check_query = "SELECT id FROM service_requests WHERE id = :request_id";



            $check_stmt = $db->prepare($check_query);



            $check_stmt->bindParam(":request_id", $request_id);



            $check_stmt->execute();



            



            if ($check_stmt->rowCount() == 0) {



                serviceJsonResponse(false, "Service request not found");



                return;



            }



            



            // Validate assigned_to if provided



            if ($assigned_to && $assigned_to > 0) {



                $user_check_query = "SELECT id FROM users WHERE id = :user_id AND role IN ('admin', 'staff')";



                $user_check_stmt = $db->prepare($user_check_query);



                $user_check_stmt->bindParam(":user_id", $assigned_to);



                $user_check_stmt->execute();



                



                if ($user_check_stmt->rowCount() == 0) {



                    serviceJsonResponse(false, "Invalid staff member assigned");



                    return;



                }



            }



            



            // Update the request



            $update_query = "UPDATE service_requests 



                             SET title = :title, description = :description, category_id = :category_id, 



                                 priority = :priority, status = :status, assigned_to = :assigned_to, assigned_at = NOW(), 



                                 updated_at = NOW() 



                             WHERE id = :request_id";



            



            $update_stmt = $db->prepare($update_query);



            $update_stmt->bindParam(":title", $title);



            $update_stmt->bindParam(":description", $description);



            $update_stmt->bindParam(":category_id", $category_id);



            $update_stmt->bindParam(":priority", $priority);



            $update_stmt->bindParam(":status", $status);



            $update_stmt->bindParam(":assigned_to", $assigned_to, PDO::PARAM_INT);



            $update_stmt->bindParam(":request_id", $request_id);



            



            if ($update_stmt->execute()) {



                // Create notifications for status changes and assignments



                try {



                    // Get request details with user info



                    $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email,



                                            a.full_name as assigned_name



                                     FROM service_requests sr



                                     LEFT JOIN users u ON sr.user_id = u.id



                                     LEFT JOIN users a ON sr.assigned_to = a.id



                                     WHERE sr.id = :request_id";



                    $request_stmt = $db->prepare($request_query);



                    $request_stmt->bindParam(":request_id", $request_id);



                    $request_stmt->execute();



                    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);



                    



                    // Get old status for comparison



                    $old_status_query = "SELECT status, assigned_to FROM service_requests WHERE id = :request_id";



                    $old_stmt = $db->prepare($old_status_query);



                    $old_stmt->bindParam(":request_id", $request_id);



                    $old_stmt->execute();



                    $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);



                    



                    // Notify request participants about status change



                    if ($old_data['status'] !== $status) {



                        $status_messages = [



                            'in_progress' => 'đang được xử lý',



                            'resolved' => 'đã được giải quyết',



                            'closed' => 'đã được đóng',



                            'rejected' => 'bị từ chối'



                        ];



                        



                        if (isset($status_messages[$status])) {



                            $title = "Yêu cầu #" . $request_id . " " . $status_messages[$status];



                            $message = "Yêu cầu '" . $request_data['title'] . "' " . $status_messages[$status];



                            $type = $status === 'resolved' ? 'success' : 'info';



                            



                            notifyRequestParticipants($db, $request_id, $user_id, $title, $message, $type);



                            



                            // Special notification when staff accepts request (status = in_progress)



                            if ($status === 'in_progress' && $assigned_to) {



                                try {



                                    // Get staff name



                                    $staff_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");



                                    $staff_stmt->execute([$assigned_to]);



                                    $staff_data = $staff_stmt->fetch(PDO::FETCH_ASSOC);



                                    



                                    $staff_name = $staff_data['full_name'] ?? 'Staff';



                                    



                                    // Notify user and admin that staff has accepted the request



                                    $accept_title = "Yêu cầu #" . $request_id . " đã được nhận";



                                    $accept_message = $staff_name . " đã nhận yêu cầu: " . $request_data['title'];



                                    



                                    // Get user and admin IDs



                                    $notify_ids = [];



                                    



                                    // Add request owner



                                    if ($request_data['user_id'] != $assigned_to) {



                                        $notify_ids[] = $request_data['user_id'];



                                    }



                                    



                                    // Add all admins



                                    $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");



                                    $admin_stmt->execute();



                                    $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);



                                    $notify_ids = array_merge($notify_ids, $admins);



                                    



                                    // Remove duplicates and exclude the staff who accepted



                                    $notify_ids = array_unique(array_diff($notify_ids, [$assigned_to]));



                                    



                                    if (!empty($notify_ids)) {



                                        // $notificationHelper = new NotificationHelper($db);



                                        // $notificationHelper->notifyUsers($notify_ids, $accept_title, $accept_message, 'success', $request_id, 'request');



                                    }



                                } catch (Exception $e) {



                                    error_log("Failed to create staff acceptance notification: " . $e->getMessage());



                                }



                            }



                            



                            // Special notification when staff resolves request (status = resolved)



                            if ($status === 'resolved' && $assigned_to) {



                                try {



                                    // Get staff name



                                    $staff_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");



                                    $staff_stmt->execute([$assigned_to]);



                                    $staff_data = $staff_stmt->fetch(PDO::FETCH_ASSOC);



                                    



                                    $staff_name = $staff_data['full_name'] ?? 'Staff';



                                    



                                    // Notify user and admin that staff has resolved the request



                                    $resolve_title = "Yêu cầu #" . $request_id . " đã được giải quyết";



                                    $resolve_message = $staff_name . " đã giải quyết yêu cầu: " . $request_data['title'];



                                    



                                    // Get user and admin IDs



                                    $notify_ids = [];



                                    



                                    // Add request owner



                                    if ($request_data['user_id'] != $assigned_to) {



                                        $notify_ids[] = $request_data['user_id'];



                                    }



                                    



                                    // Add all admins



                                    $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");



                                    $admin_stmt->execute();



                                    $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);



                                    $notify_ids = array_merge($notify_ids, $admins);



                                    



                                    // Remove duplicates and exclude the staff who resolved



                                    $notify_ids = array_unique(array_diff($notify_ids, [$assigned_to]));



                                    



                                    if (!empty($notify_ids)) {



                                        // $notificationHelper = new NotificationHelper($db);



                                        // $notificationHelper->notifyUsers($notify_ids, $resolve_title, $resolve_message, 'success', $request_id, 'request');



                                    }



                                } catch (Exception $e) {



                                    error_log("Failed to create staff resolution notification: " . $e->getMessage());



                                }



                            }



                            



                            // Special notification when user closes request (status = closed)



                            if ($status === 'closed') {



                                try {



                                    // Get user name who closed the request



                                    $user_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");



                                    $user_stmt->execute([$user_id]);



                                    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);



                                    



                                    $user_name = $user_data['full_name'] ?? 'User';



                                    



                                    // Notify staff and admin that user has closed the request



                                    $close_title = "Yêu cầu #" . $request_id . " đã được đóng";



                                    $close_message = $user_name . " đã đóng yêu cầu: " . $request_data['title'];



                                    



                                    // Get staff and admin IDs



                                    $notify_ids = [];



                                    



                                    // Add assigned staff if exists



                                    if ($request_data['assigned_to'] && $request_data['assigned_to'] != $user_id) {



                                        $notify_ids[] = $request_data['assigned_to'];



                                    }



                                    



                                    // Add all staff and admins



                                    $staff_admin_stmt = $db->prepare("SELECT id FROM users WHERE role IN ('staff', 'admin')");



                                    $staff_admin_stmt->execute();



                                    $staff_admins = $staff_admin_stmt->fetchAll(PDO::FETCH_COLUMN);



                                    $notify_ids = array_merge($notify_ids, $staff_admins);



                                    



                                    // Remove duplicates and exclude the user who closed



                                    $notify_ids = array_unique(array_diff($notify_ids, [$user_id]));



                                    



                                    if (!empty($notify_ids)) {



                                        // $notificationHelper = new NotificationHelper($db);



                                        // $notificationHelper->notifyUsers($notify_ids, $close_title, $close_message, 'info', $request_id, 'request');



                                    }



                                } catch (Exception $e) {



                                    error_log("Failed to create user close notification: " . $e->getMessage());



                                }



                            }



                        }



                    }



                    



                    // Notify assigned user if assignment changed



                    if ($assigned_to && $old_data['assigned_to'] != $assigned_to) {



                        $title = "Yêu cầu mới được giao #" . $request_id;



                        $message = "Bạn được giao yêu cầu: " . $request_data['title'];



                        



                        createNotification($db, $assigned_to, $title, $message, 'info', $request_id, 'assignment');



                    }



                } catch (Exception $e) {



                    error_log("Failed to create update notifications: " . $e->getMessage());



                    // Continue even if notification creation fails



                }



                



                serviceJsonResponse(true, "Service request updated successfully");



            } else {



                serviceJsonResponse(false, "Failed to update service request");



            }



        } catch (Exception $e) {



            serviceJsonResponse(false, "Database error: " . $e->getMessage());



        }



    }



    



    elseif (trim($action) == 'reject_request') {



        error_log("ENTERING REJECT_REQUEST BRANCH!");



        // Handle both JSON and FormData (file upload) requests



        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';



        



        if (strpos($content_type, 'multipart/form-data') !== false) {



            // Handle FormData (file upload)



            $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;



            $reject_reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : '';



            $reject_details = isset($_POST['reject_details']) ? trim($_POST['reject_details']) : '';



            



            error_log("FormData reject request - request_id: $request_id, reject_reason: $reject_reason");



            error_log("FILES data: " . print_r($_FILES, true));



        } else {



            // Handle JSON



            $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;



            $reject_reason = isset($input['reject_reason']) ? trim($input['reject_reason']) : '';



            $reject_details = isset($input['reject_details']) ? trim($input['reject_details']) : '';



        }



        



        if ($request_id <= 0 || empty($reject_reason)) {



            serviceJsonResponse(false, "Request ID and reject reason are required");



            return;



        }



        



        // Only staff can reject requests



        if ($user_role != 'staff') {



            serviceJsonResponse(false, "Access denied");



            return;



        }



        



        try {



            // Check if request exists and user has access (staff can reject any request they can see)



            $check_query = "SELECT id, assigned_to, status, user_id FROM service_requests 



                           WHERE id = :request_id";



            $check_stmt = $db->prepare($check_query);



            $check_stmt->bindParam(":request_id", $request_id);



            $check_stmt->bindParam(":user_id", $user_id);



            $check_stmt->execute();



            



            if ($check_stmt->rowCount() == 0) {



                serviceJsonResponse(false, "Request not found");



                return;



            }



            



            $request_data = $check_stmt->fetch(PDO::FETCH_ASSOC);



            



            // Staff cannot reject their own request



            if ($request_data['user_id'] == $user_id) {



                serviceJsonResponse(false, "You cannot reject your own request");



                return;



            }



            



            // Check if reject request already exists



            $existing_query = "SELECT id FROM reject_requests 



                               WHERE service_request_id = :request_id AND status = 'pending'";



            $existing_stmt = $db->prepare($existing_query);



            $existing_stmt->bindParam(":request_id", $request_id);



            $existing_stmt->execute();



            



            if ($existing_stmt->rowCount() > 0) {



                serviceJsonResponse(false, "Reject request already exists for this service request");



                return;



            }



            



            // Create reject request



            $insert_query = "INSERT INTO reject_requests 



                             (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 



                             VALUES (:request_id, :rejected_by, :reject_reason, :reject_details, 'pending', NOW())";



            $insert_stmt = $db->prepare($insert_query);



            $insert_stmt->bindParam(":request_id", $request_id);



            $insert_stmt->bindParam(":rejected_by", $user_id);



            $insert_stmt->bindParam(":reject_reason", $reject_reason);



            $insert_stmt->bindParam(":reject_details", $reject_details);



            



            if ($insert_stmt->execute()) {



                $reject_id = $db->lastInsertId();



                



                // Handle file uploads if any



                $uploaded_files = [];



                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {



                    error_log("Processing file uploads for reject request $reject_id");



                    



                    $uploads_dir = __DIR__ . '/../uploads/reject_requests/';



                    if (!file_exists($uploads_dir)) {



                        mkdir($uploads_dir, 0755, true);



                    }



                    



                    $file_attachments = $_FILES['attachments'];



                    $file_count = count($file_attachments['name']);



                    



                    for ($i = 0; $i < $file_count; $i++) {



                        if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {



                            $original_name = $file_attachments['name'][$i];



                            $file_size = $file_attachments['size'][$i];



                            $file_tmp = $file_attachments['tmp_name'][$i];



                            $file_type = $file_attachments['type'][$i];



                            



                            // Generate unique filename



                            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);



                            $unique_filename = uniqid('reject_', true) . '.' . $file_extension;



                            $file_path = $uploads_dir . $unique_filename;



                            



                            // Validate file (size, type)



                            $max_size = 10 * 1024 * 1024; // 10MB



                            $allowed_types = [



                                'image/jpeg', 'image/png', 'image/gif',



                                'application/pdf', 'application/msword',



                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',



                                'application/vnd.ms-excel',



                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',



                                'application/vnd.ms-powerpoint',



                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',



                                'text/plain', 'application/zip'



                            ];



                            



                            if ($file_size > $max_size) {



                                error_log("File too large: $original_name ($file_size bytes)");



                                continue;



                            }



                            



                            if (!in_array($file_type, $allowed_types)) {



                                error_log("File type not allowed: $original_name ($file_type)");



                                continue;



                            }



                            



                            // Move file



                            if (move_uploaded_file($file_tmp, $file_path)) {



                                // Save to database



                                $attachment_stmt = $db->prepare("



                                    INSERT INTO reject_request_attachments 



                                    (reject_request_id, original_name, filename, file_size, mime_type, uploaded_at)



                                    VALUES (?, ?, ?, ?, ?, NOW())



                                ");



                                



                                if ($attachment_stmt->execute([$reject_id, $original_name, $unique_filename, $file_size, $file_type])) {



                                    $uploaded_files[] = [



                                        'original_name' => $original_name,



                                        'filename' => $unique_filename,



                                        'file_size' => $file_size,



                                        'mime_type' => $file_type



                                    ];



                                    error_log("Successfully uploaded: $original_name");



                                } else {



                                    error_log("Failed to save attachment to database: $original_name");



                                    // Clean up uploaded file



                                    unlink($file_path);



                                }



                            } else {



                                error_log("Failed to move uploaded file: $original_name");



                            }



                        } else {



                            error_log("Upload error for file $i: " . $file_attachments['error'][$i]);



                        }



                    }



                    



                    error_log("Uploaded " . count($uploaded_files) . " files for reject request $reject_id");



                }



                



                // Create notifications for admin users



                try {



                    // Get staff name and request details



                    $staff_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");



                    $staff_stmt->execute([$user_id]);



                    $staff_data = $staff_stmt->fetch(PDO::FETCH_ASSOC);



                    



                    $request_stmt = $db->prepare("SELECT title FROM service_requests WHERE id = ?");



                    $request_stmt->execute([$request_id]);



                    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);



                    



                    $staff_name = $staff_data['full_name'] ?? 'Staff';



                    $request_title = $request_data['title'] ?? 'Unknown';



                    



                    $title = "Yêu cầu từ chối mới #" . $db->lastInsertId();



                    $message = $staff_name . " từ chối yêu cầu: " . $request_title;



                    



                    // Notify all admin users



                    $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");



                    $admin_stmt->execute();



                    $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);



                    



                    if (!empty($admins)) {



                        // $notificationHelper = new NotificationHelper($db);



                        foreach ($admins as $admin_id) {



                            // $notificationHelper->createNotification($admin_id, $title, $message, 'warning', $request_id, 'reject_request', false);



                        }



                    }



                } catch (Exception $e) {



                    error_log("Failed to create reject request notifications: " . $e->getMessage());



                    // Continue even if notification creation fails



                }



                



                $response_data = [



                    'success' => true,



                    'message' => 'Reject request submitted successfully',



                    'reject_id' => $reject_id



                ];



                



                // Include uploaded files information if any



                if (!empty($uploaded_files)) {



                    $response_data['uploaded_files'] = $uploaded_files;



                    $response_data['message'] .= ' with ' . count($uploaded_files) . ' file(s) attached';



                }



                



                echo json_encode($response_data);



            } else {



                serviceJsonResponse(false, "Failed to submit reject request");



            }



        } catch (Exception $e) {



            serviceJsonResponse(false, "Database error: " . $e->getMessage());



        }



    }



    



    elseif ($action == 'accept_request') {



        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;



        



        if ($request_id <= 0) {



            serviceJsonResponse(false, "Request ID is required");



            return;



        }



        



        // Only staff can accept requests



        if ($user_role != 'staff') {



            serviceJsonResponse(false, "Access denied");



            return;



        }



        



        // Additional session check to ensure $user_id is properly set



        if (!$user_id) {



            serviceJsonResponse(false, "Session expired");



            return;



        }



        



        try {



            // Check if request exists and is available for assignment



            // Available statuses: 'open' or 'request_support' (when support request is rejected)



            $check_query = "SELECT id, assigned_to, status FROM service_requests 



                           WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 



                           AND (assigned_to IS NULL OR assigned_to = 0)";



            $check_stmt = $db->prepare($check_query);



            $check_stmt->bindParam(":request_id", $request_id);



            $check_stmt->execute();



            



            if ($check_stmt->rowCount() == 0) {



                // Get detailed info for debugging



                $debug_query = "SELECT id, assigned_to, status FROM service_requests WHERE id = :request_id";



                $debug_stmt = $db->prepare($debug_query);



                $debug_stmt->bindParam(":request_id", $request_id);



                $debug_stmt->execute();



                $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);



                



                if ($debug_info) {



                    $status = $debug_info['status'];



                    $assigned = $debug_info['assigned_to'];



                    serviceJsonResponse(false, "Request not available for assignment. Current status: '$status', Assigned to: '$assigned'");



                } else {



                    serviceJsonResponse(false, "Request not found with ID: $request_id");



                }



                return;



            }



            



            // Update request to assign to staff and set to in_progress



            $update_query = "UPDATE service_requests 



                           SET assigned_to = :user_id, status = 'in_progress', 
                               assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 



                           WHERE id = :request_id";



            $update_stmt = $db->prepare($update_query);



            $update_stmt->bindParam(":request_id", $request_id);



            $update_stmt->bindParam(":user_id", $user_id);



            



            if ($update_stmt->execute()) {



                // Get request details for email notification AFTER the update



                $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 



                                         staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name



                                  FROM service_requests sr



                                  LEFT JOIN users u ON sr.user_id = u.id



                                  LEFT JOIN users staff ON sr.assigned_to = staff.id



                                  LEFT JOIN categories c ON sr.category_id = c.id



                                  WHERE sr.id = :request_id";



                $request_stmt = $db->prepare($request_query);



                $request_stmt->bindParam(":request_id", $request_id);



                $request_stmt->execute();



                $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);



                



                // Send email notification to requester about assignment (only if current user is not the requester)



                if ($request_data['user_id'] != $user_id) {



                    try {



                        $emailHelper = new PHPMailerEmailHelper(); // Use PHPMailerEmailHelper for actual email sending



                        $emailHelper->sendStatusUpdateNotification($request_data, $request_data['assigned_name']);



                    } catch (Exception $e) {



                        error_log("Email notification failed: " . $e->getMessage());



                        // Continue even if email fails



                    }



                }



                



                // Send role-based notifications

                try {

                    $notificationHelper = new ServiceRequestNotificationHelper();

                    

                    // Notify user that their request is now in progress

                    $notificationHelper->notifyUserRequestInProgress(

                        $request_id, 

                        $request_data['user_id'], 

                        $request_data['assigned_name']

                    );

                    

                    // Notify admin about staff acceptance

                    $notificationHelper->notifyAdminStatusChange(

                        $request_id, 

                        'open', 

                        'in_progress', 

                        $request_data['assigned_name'], 

                        $request_data['title']

                    );

                    

                } catch (Exception $e) {

                    error_log("Failed to send role-based notifications for staff acceptance: " . $e->getMessage());

                }



                



                serviceJsonResponse(true, "Request accepted successfully");



            } else {



                serviceJsonResponse(false, "Failed to accept request");



            }



        } catch (Exception $e) {



            serviceJsonResponse(false, "Database error: " . $e->getMessage());



        }



    }



    



    elseif ($action == 'resolve') {



        $request_id = isset($input['id']) ? (int)$input['id'] : 0;



        $error_description = isset($input['error_description']) ? trim($input['error_description']) : '';



        $error_type = isset($input['error_type']) ? trim($input['error_type']) : '';



        $replacement_materials = isset($input['replacement_materials']) ? trim($input['replacement_materials']) : '';



        $solution_method = isset($input['solution_method']) ? trim($input['solution_method']) : '';



        



        if ($request_id <= 0 || empty($error_description) || empty($error_type) || empty($solution_method)) {



            serviceJsonResponse(false, "Request ID, error description, error type, and solution method are required");



            return;



        }



        



        // Only staff can resolve requests



        if ($user_role != 'staff') {



            serviceJsonResponse(false, "Access denied. Staff access required.");



            return;



        }



        



        try {



            // Check if request exists and is assigned to current user



            $check_query = "SELECT id, assigned_to, status FROM service_requests 



                           WHERE id = :request_id AND assigned_to = :user_id AND status = 'in_progress'";



            $check_stmt = $db->prepare($check_query);



            $check_stmt->bindParam(":request_id", $request_id);



            $check_stmt->bindParam(":user_id", $user_id);



            $check_stmt->execute();



            



            if ($check_stmt->rowCount() == 0) {



                serviceJsonResponse(false, "Request not found or not assigned to you or not in progress");



                return;



            }



            



            // Start transaction



            $db->beginTransaction();



            



            // Insert resolution record



            $insert_resolution_query = "INSERT INTO resolutions 



                                      (service_request_id, error_description, error_type, replacement_materials, solution_method, resolved_by) 



                                      VALUES (:request_id, :error_description, :error_type, :replacement_materials, :solution_method, :resolved_by)";



            $insert_resolution_stmt = $db->prepare($insert_resolution_query);



            $insert_resolution_stmt->bindParam(":request_id", $request_id);



            $insert_resolution_stmt->bindParam(":error_description", $error_description);



            $insert_resolution_stmt->bindParam(":error_type", $error_type);



            $insert_resolution_stmt->bindParam(":replacement_materials", $replacement_materials);



            $insert_resolution_stmt->bindParam(":solution_method", $solution_method);



            $insert_resolution_stmt->bindParam(":resolved_by", $user_id);



            



            if (!$insert_resolution_stmt->execute()) {



                $db->rollBack();



                serviceJsonResponse(false, "Failed to create resolution record");



                return;



            }



            



            // Update service request status to resolved



            $update_request_query = "UPDATE service_requests 



                                    SET status = 'resolved', updated_at = NOW() 



                                    WHERE id = :request_id";



            $update_request_stmt = $db->prepare($update_request_query);



            $update_request_stmt->bindParam(":request_id", $request_id);



            



            if (!$update_request_stmt->execute()) {



                $db->rollBack();



                serviceJsonResponse(false, "Failed to update request status");



                return;



            }



            



            // Get request details for email notification



            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 



                                     staff.full_name as staff_name, c.name as category_name



                              FROM service_requests sr



                              LEFT JOIN users u ON sr.user_id = u.id



                              LEFT JOIN users staff ON sr.assigned_to = staff.id



                              LEFT JOIN categories c ON sr.category_id = c.id



                              WHERE sr.id = :request_id";



            $request_stmt = $db->prepare($request_query);



            $request_stmt->bindParam(":request_id", $request_id);



            $request_stmt->execute();



            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);



            



            // Send email notification to requester about resolution (only if current user is not the requester)



            if ($request_data['user_id'] != $user_id) {



                try {



                    $emailHelper = new PHPMailerEmailHelper();



                    $emailHelper->sendResolutionNotification($request_data, $error_description, $solution_method);



                } catch (Exception $e) {



                    error_log("Email notification failed: " . $e->getMessage());



                    // Continue even if email fails



                }



            }



            



            // Also notify assigned user about resolution (if different from current user)



            if ($request_data['assigned_to'] && $request_data['assigned_to'] != $user_id) {



                try {



                    $title = "Yêu cầu được giao cho bạn đã được giải quyết #" . $request_id;



                    $message = "Yêu cầu '" . $request_data['title'] . "' đã được giải quyết.";



                    createNotification($db, $request_data['assigned_to'], $title, $message, 'success', $request_id, 'request');



                } catch (Exception $e) {



                    error_log("Failed to notify assigned user about resolution: " . $e->getMessage());



                }



            }



            



            $db->commit();



            serviceJsonResponse(true, "Request resolved successfully");



            



        } catch (Exception $e) {



            $db->rollBack();



            serviceJsonResponse(false, "Database error: " . $e->getMessage());



        }



    }



    

}





if ($method == 'DELETE') {



    // Delete service request (admin only)



    if ($user_role != 'admin') {



        serviceJsonResponse(false, "Access denied. Admin access required.");



        return;



    }



    



    $request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;



    $force_delete = isset($_GET['force']) ? $_GET['force'] === 'true' : false;



    



    if ($request_id <= 0) {



        serviceJsonResponse(false, "Request ID is required");



        return;



    }



    



    try {



        // Check if request exists



        $check_query = "SELECT id, title FROM service_requests WHERE id = :request_id";



        $check_stmt = $db->prepare($check_query);



        $check_stmt->bindParam(":request_id", $request_id);



        $check_stmt->execute();



        



        if ($check_stmt->rowCount() == 0) {



            serviceJsonResponse(false, "Service request not found");



            return;



        }



        



        $request = $check_stmt->fetch(PDO::FETCH_ASSOC);



        



        // Check for foreign key constraints and get counts for confirmation



        $constraints = [];



        



        // Check comments



        $comments_query = "SELECT COUNT(*) as count FROM comments WHERE service_request_id = :request_id";



        $comments_stmt = $db->prepare($comments_query);



        $comments_stmt->bindParam(":request_id", $request_id);



        $comments_stmt->execute();



        $comments_count = $comments_stmt->fetch(PDO::FETCH_ASSOC)['count'];



        if ($comments_count > 0) {



            $constraints[] = "{$comments_count} bình luận";



        }



        



        // Check attachments



        $attachments_query = "SELECT COUNT(*) as count FROM attachments WHERE service_request_id = :request_id";



        $attachments_stmt = $db->prepare($attachments_query);



        $attachments_stmt->bindParam(":request_id", $request_id);



        $attachments_stmt->execute();



        $attachments_count = $attachments_stmt->fetch(PDO::FETCH_ASSOC)['count'];



        if ($attachments_count > 0) {



            $constraints[] = "{$attachments_count} tệp đính kèm";



        }



        



        // Check resolutions



        $resolutions_query = "SELECT COUNT(*) as count FROM resolutions WHERE service_request_id = :request_id";



        $resolutions_stmt = $db->prepare($resolutions_query);



        $resolutions_stmt->bindParam(":request_id", $request_id);



        $resolutions_stmt->execute();



        $resolutions_count = $resolutions_stmt->fetch(PDO::FETCH_ASSOC)['count'];



        if ($resolutions_count > 0) {



            $constraints[] = "{$resolutions_count} giải quyết";



        }



        



        // Check support requests



        $support_query = "SELECT COUNT(*) as count FROM support_requests WHERE service_request_id = :request_id";



        $support_stmt = $db->prepare($support_query);



        $support_stmt->bindParam(":request_id", $request_id);



        $support_stmt->execute();



        $support_count = $support_stmt->fetch(PDO::FETCH_ASSOC)['count'];



        if ($support_count > 0) {



            $constraints[] = "{$support_count} yêu cầu hỗ trợ";



        }



        



        // Check reject requests



        $reject_query = "SELECT COUNT(*) as count FROM reject_requests WHERE service_request_id = :request_id";



        $reject_stmt = $db->prepare($reject_query);



        $reject_stmt->bindParam(":request_id", $request_id);



        $reject_stmt->execute();



        $reject_count = $reject_stmt->fetch(PDO::FETCH_ASSOC)['count'];



        if ($reject_count > 0) {



            $constraints[] = "{$reject_count} yêu cầu từ chối";



        }



        



        // Show confirmation message with related data counts



        if (!empty($constraints) && !$force_delete) {



            $constraint_list = implode(", ", $constraints);



            serviceJsonResponse(false, "Xóa yêu cầu '{$request['title']}' sẽ xóa cả các dữ liệu liên quan: {$constraint_list}. Bạn có chắc chắn muốn tiếp tục?", "confirm_delete");



            return;



        }



        



        // Start transaction for cascade deletion



        $db->beginTransaction();



        



        try {



            // Delete related data in correct order to respect foreign keys



            



            // Delete comments first



            if ($comments_count > 0) {



                $delete_comments = "DELETE FROM comments WHERE service_request_id = :request_id";



                $delete_comments_stmt = $db->prepare($delete_comments);



                $delete_comments_stmt->bindParam(":request_id", $request_id);



                $delete_comments_stmt->execute();



            }



            



            // Delete attachments



            if ($attachments_count > 0) {



                $delete_attachments = "DELETE FROM attachments WHERE service_request_id = :request_id";



                $delete_attachments_stmt = $db->prepare($delete_attachments);



                $delete_attachments_stmt->bindParam(":request_id", $request_id);



                $delete_attachments_stmt->execute();



            }



            



            // Delete resolutions



            if ($resolutions_count > 0) {



                $delete_resolutions = "DELETE FROM resolutions WHERE service_request_id = :request_id";



                $delete_resolutions_stmt = $db->prepare($delete_resolutions);



                $delete_resolutions_stmt->bindParam(":request_id", $request_id);



                $delete_resolutions_stmt->execute();



            }



            



            // Delete support requests



            if ($support_count > 0) {



                $delete_support = "DELETE FROM support_requests WHERE service_request_id = :request_id";



                $delete_support_stmt = $db->prepare($delete_support);



                $delete_support_stmt->bindParam(":request_id", $request_id);



                $delete_support_stmt->execute();



            }



            



            // Delete reject requests



            if ($reject_count > 0) {



                $delete_reject = "DELETE FROM reject_requests WHERE service_request_id = :request_id";



                $delete_reject_stmt = $db->prepare($delete_reject);



                $delete_reject_stmt->bindParam(":request_id", $request_id);



                $delete_reject_stmt->execute();



            }



            



            // Finally delete the service request



            $delete_query = "DELETE FROM service_requests WHERE id = :request_id";



            $delete_stmt = $db->prepare($delete_query);



            $delete_stmt->bindParam(":request_id", $request_id);



            



            if ($delete_stmt->execute()) {



                $db->commit();



                $deleted_items = [];



                if ($comments_count > 0) $deleted_items[] = "{$comments_count} bình luận";



                if ($attachments_count > 0) $deleted_items[] = "{$attachments_count} tệp đính kèm";



                if ($resolutions_count > 0) $deleted_items[] = "{$resolutions_count} giải quyết";



                if ($support_count > 0) $deleted_items[] = "{$support_count} yêu cầu hỗ trợ";



                if ($reject_count > 0) $deleted_items[] = "{$reject_count} yêu cầu từ chối";



                



                $deleted_text = !empty($deleted_items) ? " (đã xóa: " . implode(", ", $deleted_items) . ")" : "";



                serviceJsonResponse(true, "Service request deleted successfully{$deleted_text}");



            } else {



                $db->rollBack();



                serviceJsonResponse(false, "Failed to delete service request");



            }



        } catch (Exception $e) {



            $db->rollBack();



            serviceJsonResponse(false, "Database error: " . $e->getMessage());



        }

    } catch (Exception $e) {

        serviceJsonResponse(false, "Database error: " . $e->getMessage());

    }

}



elseif ($method == 'PUT') {

    // Handle PUT requests (accept_request, resolve, etc.)

    

    // Debug: Log raw input

    $raw_input = file_get_contents('php://input');

    error_log("PUT raw input: " . $raw_input);

    

    $input = json_decode($raw_input, true);

    

    if (!$input) {

        error_log("PUT JSON decode failed, raw input: " . $raw_input);

        serviceJsonResponse(false, "Invalid JSON data");

        return;

    }

    

    error_log("PUT parsed input: " . print_r($input, true));

    

    $action = isset($input['action']) ? $input['action'] : '';

    error_log("PUT action: " . $action);

    

    if ($action == 'accept_request') {

        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

        

        if ($request_id <= 0) {

            serviceJsonResponse(false, "Request ID is required");

            return;

        }

        

        // Only staff can accept requests

        if ($user_role != 'staff' && $user_role != 'admin') {

            serviceJsonResponse(false, "Access denied - Only staff and admin can reject requests");

            return;

        }

        

        // Additional session check to ensure $user_id is properly set

        if (!$user_id) {

            serviceJsonResponse(false, "Session expired");

            return;

        }

        

        try {

            // Check if request exists and is available for assignment

            // Available statuses: 'open' or 'request_support' (when support request is rejected)

            $check_query = "SELECT id, assigned_to, status FROM service_requests 

                           WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 

                           AND (assigned_to IS NULL OR assigned_to = 0)";

            $check_stmt = $db->prepare($check_query);

            $check_stmt->bindParam(":request_id", $request_id);

            $check_stmt->execute();

            

            if ($check_stmt->rowCount() == 0) {

                // Get detailed info for debugging

                $debug_query = "SELECT id, assigned_to, status FROM service_requests WHERE id = :request_id";

                $debug_stmt = $db->prepare($debug_query);

                $debug_stmt->bindParam(":request_id", $request_id);

                $debug_stmt->execute();

                $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);

                

                if ($debug_info) {

                    $status = $debug_info['status'];

                    $assigned = $debug_info['assigned_to'];

                    serviceJsonResponse(false, "Request not available for assignment. Current status: '$status', Assigned to: '$assigned'");

                } else {

                    serviceJsonResponse(false, "Request not found with ID: $request_id");

                }

                return;

            }

            

// Update request to assign to staff and set to in_progress

$update_query = "UPDATE service_requests 
                           SET assigned_to = :user_id, status = 'in_progress', 
                               assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                           WHERE id = :request_id";

$update_stmt = $db->prepare($update_query);

$update_stmt->bindParam(":request_id", $request_id);

            $update_stmt->bindParam(":user_id", $user_id);

            

            if ($update_stmt->execute()) {

                // Quick response to user

                serviceJsonResponse(true, "Request accepted successfully");

                

                // Send email and notifications asynchronously (after response)

                // This makes the API response much faster

                register_shutdown_function(function() use ($request_id, $user_id, $db) {

                    try {

                        // Get request details for notifications

                        $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 

                                                 staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name

                                          FROM service_requests sr

                                          LEFT JOIN users u ON sr.user_id = u.id

                                          LEFT JOIN users staff ON sr.assigned_to = staff.id

                                          LEFT JOIN categories c ON sr.category_id = c.id

                                          WHERE sr.id = :request_id";

                        $request_stmt = $db->prepare($request_query);

                        $request_stmt->bindParam(":request_id", $request_id);

                        $request_stmt->execute();

                        $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

                        

                        // Send notifications using ServiceRequestNotificationHelper
                        
                        try {
                            
                            require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php';
                            
                            $notificationHelper = new ServiceRequestNotificationHelper();
                            
                            
                            // 1. Notify user that request is in progress
                            
                            $notificationHelper->notifyUserRequestInProgress(
                                
                                $request_id, 
                                
                                $request_data['user_id'], 
                                
                                $request_data['assigned_name']
                                
                            );
                            
                            
                            // 2. Notify admins about assignment
                            
                            $notificationHelper->notifyAdminStatusChange(
                                
                                $request_id, 
                                
                                'open', 
                                
                                'in_progress', 
                                
                                $request_data['assigned_name'], 
                                
                                $request_data['title']
                                
                            );
                            
                            
                                                        
                            
                            error_log("Notifications sent for request #$request_id acceptance");
                            
                        } catch (Exception $e) {
                            
                            error_log("Failed to send notifications for request #$request_id: " . $e->getMessage());
                            
                        }
                        
                        
                        // Send email notification to requester
                        
                        try {
                            
                            $emailHelper = new PHPMailerEmailHelper();
                            
                            $emailHelper->sendStatusUpdateNotification($request_data, $request_data['assigned_name']);
                            
                        } catch (Exception $e) {
                            
                            error_log("Email notification failed: " . $e->getMessage());
                            
                        }

                    } catch (Exception $e) {

                        error_log("Background notification failed: " . $e->getMessage());

                    }

                });

                

                return; // Exit early to send response faster

            } else {

                serviceJsonResponse(false, "Failed to accept request");

            }

        } catch (Exception $e) {

            serviceJsonResponse(false, "Database error: " . $e->getMessage());

        }

    }

    elseif ($action == 'update_status') {

        $request_id = isset($input['id']) ? (int)$input['id'] : 0;

        $status = isset($input['status']) ? trim($input['status']) : '';

        

        if ($request_id <= 0) {

            serviceJsonResponse(false, "Request ID is required");

            return;

        }

        

        if (empty($status)) {

            serviceJsonResponse(false, "Status is required");

            return;

        }

        

        // Only admin can update status

        if ($user_role != 'admin') {

            serviceJsonResponse(false, "Access denied");

            return;

        }

        

        // Validate status

        $valid_statuses = ['open', 'in_progress', 'resolved', 'closed', 'cancelled'];

        if (!in_array($status, $valid_statuses)) {

            serviceJsonResponse(false, "Invalid status");

            return;

        }

        

        try {

            // Get current request details before update

            $request_details_query = "SELECT sr.*, u.full_name as requester_name, c.name as category_name,

                                      assigned.full_name as assigned_name

                                      FROM service_requests sr

                                      LEFT JOIN users u ON sr.user_id = u.id

                                      LEFT JOIN categories c ON sr.category_id = c.id

                                      LEFT JOIN users assigned ON sr.assigned_to = assigned.id

                                      WHERE sr.id = :request_id";

            $details_stmt = $db->prepare($request_details_query);

            $details_stmt->bindParam(":request_id", $request_id);

            $details_stmt->execute();

            $requestDetails = $details_stmt->fetch(PDO::FETCH_ASSOC);

            

            if (!$requestDetails) {

                serviceJsonResponse(false, "Request not found");

                return;

            }

            

            $oldStatus = $requestDetails['status'];

            

            // Update request status

            // If status is 'open', reset assignment to allow staff to accept again

            if ($status === 'open') {

                $update_query = "UPDATE service_requests 

                               SET status = :status, 

                                   assigned_to = NULL 

                               WHERE id = :request_id";

            } else {

                $update_query = "UPDATE service_requests SET status = :status WHERE id = :request_id";

            }

            

            $update_stmt = $db->prepare($update_query);

            $update_stmt->bindParam(":status", $status);

            $update_stmt->bindParam(":request_id", $request_id);

            

            if ($update_stmt->execute()) {

                // Send role-based notifications based on status change

                $notificationHelper = new ServiceRequestNotificationHelper();

                

                // User notifications

                if ($oldStatus !== $status) {

                    switch ($status) {

                        case 'in_progress':

                            $notificationHelper->notifyUserRequestInProgress(

                                $request_id, 

                                $requestDetails['user_id'], 

                                $requestDetails['assigned_name']

                            );

                            break;

                        case 'pending_approval':

                            $notificationHelper->notifyUserRequestPendingApproval(

                                $request_id, 

                                $requestDetails['user_id']

                            );

                            break;

                        case 'resolved':

                            $notificationHelper->notifyUserRequestResolved(

                                $request_id, 

                                $requestDetails['user_id'], 

                                $requestDetails['error_description']

                            );

                            break;

                        case 'closed':

                        case 'cancelled':

                            // Handle closed/cancelled notifications if needed

                            break;

                    }

                }

                

                // Staff notifications - notify about admin decisions

                if ($oldStatus !== $status) {

                    switch ($status) {

                        case 'in_progress':

                            // Admin approved request, notify staff to start working

                            $notificationHelper->notifyStaffAdminApproved(

                                $request_id, 

                                $requestDetails['title'], 

                                $_SESSION['full_name'] ?? 'Admin'

                            );

                            break;

                        case 'rejected':

                            // Admin rejected request, notify staff to stop working

                            $notificationHelper->notifyStaffAdminRejected(

                                $request_id, 

                                $requestDetails['title'], 

                                $_SESSION['full_name'] ?? 'Admin'

                            );

                            break;

                    }

                }

                // Admin notification for status change tracking
                if ($oldStatus !== $status) {
                    $notificationHelper->notifyAdminStatusChange(
                        $request_id,
                        $oldStatus,
                        $status,
                        $_SESSION['full_name'] ?? 'Staff',
                        $requestDetails['title']
                    );
                }

                serviceJsonResponse(true, "Request status updated successfully");

            } else {

                serviceJsonResponse(false, "Failed to update request status");

            }

        } catch (Exception $e) {

            serviceJsonResponse(false, "Database error: " . $e->getMessage());

        }

    }

    elseif ($action == 'update') {

        // Handle full request update (admin only)

        error_log("=== UPDATE ACTION DEBUG ===");

        error_log("Raw input: " . file_get_contents('php://input'));

        

        $request_id = isset($input['id']) ? (int)$input['id'] : 0;

        $title = isset($input['title']) ? trim($input['title']) : '';

        $description = isset($input['description']) ? trim($input['description']) : '';

        $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;

        $priority = isset($input['priority']) ? $input['priority'] : 'medium';

        $status = isset($input['status']) ? trim($input['status']) : '';

        $assigned_to = isset($input['assigned_to']) ? (int)$input['assigned_to'] : null;

        

        error_log("Parsed data - ID: $request_id, Status: '$status', Assigned_to: " . ($assigned_to ?? 'NULL'));

        error_log("==========================");

        

        if ($request_id <= 0) {

            serviceJsonResponse(false, "Request ID is required");

            return;

        }

        

        // Only admin can update requests

        if ($user_role != 'admin') {

            serviceJsonResponse(false, "Access denied");

            return;

        }

        

        // Validate required fields

        if (empty($title) || empty($description) || $category_id <= 0) {

            serviceJsonResponse(false, "Title, description, and category are required");

            return;

        }

        

        // Validate status

        $valid_statuses = ['open', 'in_progress', 'resolved', 'closed', 'cancelled'];

        if (!empty($status) && !in_array($status, $valid_statuses)) {

            serviceJsonResponse(false, "Invalid status");

            return;

        }

        

        // Validate priority

        $valid_priorities = ['low', 'medium', 'high'];

        if (!in_array($priority, $valid_priorities)) {

            serviceJsonResponse(false, "Invalid priority");

            return;

        }

        

        try {

            // Update request

            // If status is 'open', reset assignment to allow staff to accept again

            if ($status === 'open') {

                $assigned_to = null; // Force reset assignment when status is set to open

            }

            

            $update_query = "UPDATE service_requests 

                           SET title = :title, description = :description, category_id = :category_id, 

                               priority = :priority, status = :status, assigned_to = :assigned_to,

                               assigned_at = CASE 

                                   WHEN assigned_to IS NOT NULL AND (SELECT assigned_to FROM service_requests WHERE id = :request_id) IS NULL THEN NOW()

                                   WHEN assigned_to IS NOT NULL AND (SELECT assigned_to FROM service_requests WHERE id = :request_id) != :assigned_to THEN NOW()

                                   ELSE assigned_at

                               END

                           WHERE id = :request_id";

            $update_stmt = $db->prepare($update_query);

            $update_stmt->bindParam(":title", $title);

            $update_stmt->bindParam(":description", $description);

            $update_stmt->bindParam(":category_id", $category_id);

            $update_stmt->bindParam(":priority", $priority);

            $update_stmt->bindParam(":status", $status);

            $update_stmt->bindParam(":assigned_to", $assigned_to);

            $update_stmt->bindParam(":request_id", $request_id);

            

            if ($update_stmt->execute()) {

                serviceJsonResponse(true, "Request updated successfully");

            } else {

                serviceJsonResponse(false, "Failed to update request");

            }

        } catch (Exception $e) {

            serviceJsonResponse(false, "Database error: " . $e->getMessage());

        }

    }

    // elseif ($action == 'resolve') {

    //     // Handle request resolution (staff only) - DISABLED - Using handleResolveRequest instead

    //     error_log("LEGACY RESOLVE HANDLER - DISABLED");

    //     serviceJsonResponse(false, "Legacy resolve handler disabled");

    // }

    elseif ($action == 'close_request') {

        // Handle request closing (user only)

        error_log("=== CLOSE REQUEST ACTION DEBUG ===");

        

        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

        $rating = isset($input['rating']) ? (int)$input['rating'] : null;

        $feedback = isset($input['feedback']) ? trim($input['feedback']) : null;

        $software_feedback = isset($input['software_feedback']) ? trim($input['software_feedback']) : null;

        $would_recommend = isset($input['would_recommend']) ? $input['would_recommend'] : null;

        $ease_of_use = isset($input['ease_of_use']) ? (int)$input['ease_of_use'] : null;

        $speed_stability = isset($input['speed_stability']) ? (int)$input['speed_stability'] : null;

        $requirement_meeting = isset($input['requirement_meeting']) ? (int)$input['requirement_meeting'] : null;

        

        error_log("Close request data - ID: $request_id, Rating: $rating");

        

        if ($request_id <= 0) {

            serviceJsonResponse(false, "Request ID is required");

            return;

        }

        

        try {

            // Check if request exists and belongs to current user or is staff/admin

            $check_query = "SELECT id, user_id, status FROM service_requests WHERE id = :request_id";

            $check_stmt = $db->prepare($check_query);

            $check_stmt->bindParam(":request_id", $request_id);

            $check_stmt->execute();

            

            $request = $check_stmt->fetch(PDO::FETCH_ASSOC);

            

            if (!$request) {

                serviceJsonResponse(false, "Request not found");

                return;

            }

            

            // Check permissions: users can only close their own requests, staff/admin can close any

            if ($user_role === 'user' && $request['user_id'] != $user_id) {

                serviceJsonResponse(false, "Access denied. You can only close your own requests.");

                return;

            }

            

            // Check if request is in a status that can be closed (resolved)

            if ($request['status'] !== 'resolved') {

                serviceJsonResponse(false, "Only resolved requests can be closed");

                return;

            }

            

            // Start transaction

            $db->beginTransaction();

            

            // Insert feedback into request_feedback table

            $feedback_query = "INSERT INTO request_feedback 

                              (service_request_id, rating, feedback, software_feedback, would_recommend, 

                               ease_of_use, speed_stability, requirement_meeting, created_by) 

                              VALUES (:request_id, :rating, :feedback, :software_feedback, :would_recommend,

                                      :ease_of_use, :speed_stability, :requirement_meeting, :created_by)";

            $feedback_stmt = $db->prepare($feedback_query);

            $feedback_stmt->bindParam(":request_id", $request_id);

            $feedback_stmt->bindParam(":rating", $rating, PDO::PARAM_INT);

            $feedback_stmt->bindParam(":feedback", $feedback);

            $feedback_stmt->bindParam(":software_feedback", $software_feedback);

            $feedback_stmt->bindParam(":would_recommend", $would_recommend);

            $feedback_stmt->bindParam(":ease_of_use", $ease_of_use, PDO::PARAM_INT);

            $feedback_stmt->bindParam(":speed_stability", $speed_stability, PDO::PARAM_INT);

            $feedback_stmt->bindParam(":requirement_meeting", $requirement_meeting, PDO::PARAM_INT);

            $feedback_stmt->bindParam(":created_by", $user_id);

            

            // Update request status to closed

            $update_query = "UPDATE service_requests 

                           SET status = 'closed', 

                               closed_at = NOW(),

                               updated_at = NOW()

                           WHERE id = :request_id";

            $update_stmt = $db->prepare($update_query);

            $update_stmt->bindParam(":request_id", $request_id);

            

            if ($feedback_stmt->execute() && $update_stmt->execute()) {

                // Commit transaction

                $db->commit();

                

                // Create role-based notifications for user feedback

                try {

                    $notificationHelper = new ServiceRequestNotificationHelper();

                    

                    // Get request details for notifications

                    $requestDetails = $notificationHelper->getRequestDetails($request_id);

                    

                    // Notify staff about user feedback

                    $notificationHelper->notifyStaffUserFeedback(

                        $request_id, 

                        $user_id, 

                        $rating, 

                        $feedback, 

                        $requestDetails['requester_name'] ?? 'Người dùng'

                    );

                    

                } catch (Exception $e) {

                    error_log("Failed to create feedback notifications: " . $e->getMessage());

                }

                

                serviceJsonResponse(true, "Request closed successfully");

            } else {

                $db->rollBack();

                serviceJsonResponse(false, "Failed to close request");

            }

        } catch (Exception $e) {

            if (isset($db) && $db->inTransaction()) {

                $db->rollBack();

            }

            serviceJsonResponse(false, "Database error: " . $e->getMessage());

        }

    }

    else {

        serviceJsonResponse(false, "Invalid action for PUT method");

    }

}

else {

    serviceJsonResponse(false, "Method not allowed");



}



// Function to handle resolve request logic

function handleResolveRequest($request_id, $error_description, $error_type, $replacement_materials, $solution_method, $attachments, $user_id, $user_role, $db) {

    error_log("=== HANDLE RESOLVE REQUEST START ===");

    error_log("Request ID: $request_id");

    error_log("User ID: $user_id, Role: $user_role");

    error_log("Error Description: $error_description");

    error_log("Error Type: $error_type");

    error_log("Solution Method: $solution_method");

    error_log("Attachments count: " . count($attachments));

    

    if ($request_id <= 0 || empty($error_description) || empty($error_type) || empty($solution_method)) {

        error_log("VALIDATION FAILED - Request ID: $request_id, Error: $error_description, Type: $error_type, Solution: $solution_method");

        serviceJsonResponse(false, "Request ID, error description, error type, and solution method are required");

        return;

    }

    

    // Only staff and admin can resolve requests

    if ($user_role != 'staff' && $user_role != 'admin') {

        error_log("ACCESS DENIED - User role: $user_role");

        serviceJsonResponse(false, "Access denied. Staff or admin access required.");

        return;

    }

    error_log("ACCESS GRANTED - User role: $user_role");

    

    try {

        // Check if request exists and is assigned to current user

        $check_query = "SELECT id, assigned_to, status FROM service_requests 

                       WHERE id = :request_id AND assigned_to = :user_id AND status = 'in_progress'";

        $check_stmt = $db->prepare($check_query);

        $check_stmt->bindParam(":request_id", $request_id);

        $check_stmt->bindParam(":user_id", $user_id);

        $check_stmt->execute();

        

        if ($check_stmt->rowCount() === 0) {

            serviceJsonResponse(false, "Request not found, not assigned to you, or not in progress");

            return;

        }

        

        // Start transaction

        $db->beginTransaction();

        

        // Update request status to resolved

        $update_query = "UPDATE service_requests 

                        SET status = 'resolved', 

                            resolved_at = NOW(),

                            error_description = :error_description,

                            error_type = :error_type,

                            replacement_materials = :replacement_materials,

                            solution_method = :solution_method

                        WHERE id = :request_id";

        $update_stmt = $db->prepare($update_query);

        $update_stmt->bindParam(":request_id", $request_id);

        $update_stmt->bindParam(":error_description", $error_description);

        $update_stmt->bindParam(":error_type", $error_type);

        $update_stmt->bindParam(":replacement_materials", $replacement_materials);

        $update_stmt->bindParam(":solution_method", $solution_method);

        $update_stmt->execute();

        

        // Insert resolution record

        $resolution_query = "INSERT INTO resolutions 

                           (service_request_id, resolved_by, error_description, error_type, 

                            replacement_materials, solution_method, resolved_at) 

                           VALUES (:request_id, :resolved_by, :error_description, :error_type, 

                                   :replacement_materials, :solution_method, NOW())";

        $resolution_stmt = $db->prepare($resolution_query);

        $resolution_stmt->bindParam(":request_id", $request_id);

        $resolution_stmt->bindParam(":resolved_by", $user_id);

        $resolution_stmt->bindParam(":error_description", $error_description);

        $resolution_stmt->bindParam(":error_type", $error_type);

        $resolution_stmt->bindParam(":replacement_materials", $replacement_materials);

        $resolution_stmt->bindParam(":solution_method", $solution_method);

        $resolution_stmt->execute();

        

        // Handle file attachments if any

        if (!empty($attachments)) {

            $resolution_id = $db->lastInsertId();

            

            foreach ($attachments as $attachment) {

                $upload_dir = __DIR__ . '/../uploads/completed/';

                if (!is_dir($upload_dir)) {

                    mkdir($upload_dir, 0755, true);

                }

                

                $file_extension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

                

                if (in_array($file_extension, $allowed_extensions)) {

                    $filename = uniqid() . '_' . basename($attachment['name']);

                    $filepath = $upload_dir . $filename;

                    

                    if (move_uploaded_file($attachment['tmp_name'], $filepath)) {

                        $file_query = "INSERT INTO complete_request_attachments 

                                     (service_request_id, filename, original_name, file_size, mime_type) 

                                     VALUES (:service_request_id, :filename, :original_name, :file_size, :mime_type)";

                        $file_stmt = $db->prepare($file_query);

                        $file_stmt->bindParam(":service_request_id", $request_id);

                        $file_stmt->bindParam(":filename", $filename);

                        $file_stmt->bindParam(":original_name", $attachment['name']);

                        $file_stmt->bindParam(":file_size", $attachment['size']);

                        $file_stmt->bindParam(":mime_type", $attachment['type']);

                        $file_stmt->execute();

                    }

                }

            }

        }

        

        // Commit transaction

        $db->commit();

        

        // Send notifications to user and admin - DISABLED TO FIX HTTP 500

        error_log("RESOLVE NOTIFICATIONS DISABLED - Fixing HTTP 500 error");

        // TODO: Fix NotificationHelper to prevent HTTP 500

        // The issue is likely in notifyUser or notifyAdmins methods

        

        error_log("RESOLVE SUCCESS - About to send JSON response");
        
        serviceJsonResponse(true, "Yêu yêu yêu yêu yêu yêu");
        
        error_log("RESOLVE SUCCESS - JSON response sent");

        

    } catch (Exception $e) {

        $db->rollBack();

        serviceJsonResponse(false, "Database error: " . $e->getMessage());

    }

}



?>

