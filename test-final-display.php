<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Estimated Completion Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><i class="fas fa-clock"></i> Test Estimated Completion Display</h2>
        
        <div class="alert alert-info">
            <h5>Testing Steps:</h5>
            <ol>
                <li>Admin sets estimated completion time</li>
                <li>All users (Admin, Staff, User) should see the estimated completion in request details</li>
                <li>Display should show: "Th?i gian d? ki?n hoàn thành: [formatted date]"</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Expected UI Display</h5>
            </div>
            <div class="card-body">
                <div class="request-meta-grid">
                    <div class="meta-item">
                        <strong>ID yêu c?u:</strong> #123
                    </div>
                    <div class="meta-item">
                        <strong>Ng??i t?o:</strong> John Doe
                    </div>
                    <div class="meta-item">
                        <strong>Email:</strong> john@example.com
                    </div>
                    <div class="meta-item">
                        <strong>Tr?ng thái:</strong> <span class="badge status-in_progress">?ang x? lý</span>
                    </div>
                    <div class="meta-item">
                        <strong>Ngày t?o:</strong> 17/04/2026 14:30
                    </div>
                    <!-- NEW: Estimated Completion Display -->
                    <div class="meta-item">
                        <strong><i class="fas fa-clock text-primary"></i> Th?i gian d? ki?n hoàn thành:</strong> 
                        <span class="text-primary fw-bold">19/04/2026 10:00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-user-shield"></i> Admin View</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Functionality:</strong></p>
                        <ul>
                            <li>Can set estimated completion</li>
                            <li>Can see estimated completion</li>
                            <li>Has datetime input</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-user-tie"></i> Staff View</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Functionality:</strong></p>
                        <ul>
                            <li>Cannot set estimated completion</li>
                            <li>Can see estimated completion</li>
                            <li>Uses it for planning</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-user"></i> User View</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Functionality:</strong></p>
                        <ul>
                            <li>Cannot set estimated completion</li>
                            <li>Can see estimated completion</li>
                            <li>Knows when to expect completion</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-success mt-4">
            <h5><i class="fas fa-check-circle"></i> Implementation Status</h5>
            <p><strong>Backend:</strong> API returns estimated_completion field in all requests</p>
            <p><strong>Frontend:</strong> Display added to request details for all users</p>
            <p><strong>Database:</strong> Column exists and stores datetime values</p>
            <hr>
            <p class="mb-0"><strong>Next:</strong> Test in production by having admin set time and checking if all users can see it!</p>
        </div>
    </div>
</body>
</html>
