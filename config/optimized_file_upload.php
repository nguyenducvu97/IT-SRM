<?php
// Optimized File Upload Processor
class OptimizedFileUpload {
    private $upload_dir;
    private $max_size = 10 * 1024 * 1024; // 10MB
    private $allowed_types = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'application/zip'
    ];
    
    public function __construct($upload_dir) {
        $this->upload_dir = $upload_dir;
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    /**
     * Process multiple files with parallel validation
     */
    public function processFiles($files, $request_id, $db) {
        if (empty($files) || empty($files['name'][0])) {
            return [];
        }
        
        $start_time = microtime(true);
        $uploaded_files = [];
        $file_count = count($files['name']);
        
        // Pre-validate all files first (fast validation)
        $valid_files = $this->preValidateFiles($files);
        
        if (empty($valid_files)) {
            error_log("No valid files to process");
            return [];
        }
        
        // Prepare batch insert data
        $attachment_data = [];
        
        // Process valid files
        foreach ($valid_files as $index => $file_info) {
            $result = $this->processSingleFile($file_info, $index);
            
            if ($result['success']) {
                $uploaded_files[] = $result['file_info'];
                $attachment_data[] = [
                    'support_request_id' => $request_id,
                    'original_name' => $result['file_info']['original_name'],
                    'filename' => $result['file_info']['filename'],
                    'file_size' => $result['file_info']['file_size'],
                    'mime_type' => $result['file_info']['mime_type']
                ];
            } else {
                error_log("Failed to process file: " . $result['error']);
            }
        }
        
        // Batch insert attachment records
        if (!empty($attachment_data)) {
            $this->batchInsertAttachments($attachment_data, $db);
        }
        
        $processing_time = round((microtime(true) - $start_time) * 1000, 2);
        error_log("Processed " . count($uploaded_files) . " files in {$processing_time}ms");
        
        return $uploaded_files;
    }
    
    /**
     * Fast pre-validation of all files
     */
    private function preValidateFiles($files) {
        $valid_files = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                error_log("Upload error for file $i: " . $files['error'][$i]);
                continue;
            }
            
            $file_info = [
                'name' => $files['name'][$i],
                'size' => $files['size'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'type' => $files['type'][$i],
                'index' => $i
            ];
            
            // Quick validation checks
            if ($file_info['size'] > $this->max_size) {
                error_log("File too large: {$file_info['name']} ({$file_info['size']} bytes)");
                continue;
            }
            
            if (!in_array($file_info['type'], $this->allowed_types)) {
                error_log("File type not allowed: {$file_info['name']} ({$file_info['type']})");
                continue;
            }
            
            $valid_files[] = $file_info;
        }
        
        return $valid_files;
    }
    
    /**
     * Process single file
     */
    private function processSingleFile($file_info, $index) {
        try {
            // Generate unique filename
            $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('req_', true) . '_' . $index . '.' . $file_extension;
            $file_path = $this->upload_dir . $unique_filename;
            
            // Move file
            if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
                return [
                    'success' => true,
                    'file_info' => [
                        'original_name' => $file_info['name'],
                        'filename' => $unique_filename,
                        'file_size' => $file_info['size'],
                        'mime_type' => $file_info['type']
                    ]
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Batch insert attachment records
     */
    private function batchInsertAttachments($attachment_data, $db) {
        try {
            $values = [];
            $params = [];
            $current_time = date('Y-m-d H:i:s');
            
            foreach ($attachment_data as $data) {
                $values[] = "(?, ?, ?, ?, ?, ?)";
                $params = array_merge($params, [
                    $data['support_request_id'],
                    $data['original_name'],
                    $data['filename'],
                    $data['file_size'],
                    $data['mime_type'],
                    $current_time
                ]);
            }
            
            $query = "INSERT INTO support_request_attachments 
                     (support_request_id, original_name, filename, file_size, mime_type, uploaded_at) 
                     VALUES " . implode(',', $values);
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            error_log("Batch inserted " . count($attachment_data) . " attachment records");
            
        } catch (Exception $e) {
            error_log("Failed to batch insert attachments: " . $e->getMessage());
        }
    }
}

// Optimized file processor for service requests
class OptimizedServiceFileUpload extends OptimizedFileUpload {
    public function __construct() {
        parent::__construct(__DIR__ . '/../uploads/service_requests/');
    }
    
    public function processServiceRequestFiles($files, $request_id, $db) {
        // Adapt for service request attachments table
        $uploaded_files = $this->processFiles($files, $request_id, $db);
        
        // Update attachment records for service requests
        if (!empty($uploaded_files)) {
            $this->updateServiceRequestAttachments($uploaded_files, $request_id, $db);
        }
        
        return $uploaded_files;
    }
    
    private function updateServiceRequestAttachments($uploaded_files, $request_id, $db) {
        try {
            $values = [];
            $params = [];
            $current_time = date('Y-m-d H:i:s');
            
            foreach ($uploaded_files as $file) {
                $values[] = "(?, ?, ?, ?, ?, ?)";
                $params = array_merge($params, [
                    $request_id,
                    $file['original_name'],
                    $file['filename'],
                    $file['file_size'],
                    $file['mime_type'],
                    $current_time
                ]);
            }
            
            $query = "INSERT INTO service_request_attachments 
                     (service_request_id, original_name, filename, file_size, mime_type, uploaded_at) 
                     VALUES " . implode(',', $values);
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Failed to update service request attachments: " . $e->getMessage());
        }
    }
}
?>
