-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default departments
INSERT INTO departments (name, description) VALUES
('Ban Giám đốc', 'Ban lãnh đạo cao nhất của công ty'),
('Phòng Kế hoạch', 'Phòng lập kế hoạch chiến lược và hoạt động'),
('Phòng Tài chính - Kế toán', 'Phòng quản lý tài chính và kế toán'),
('Phòng Nhân sự', 'Phòng quản lý nhân sự và tuyển dụng'),
('Phòng Kinh doanh', 'Phòng phát triển kinh doanh và bán hàng'),
('Phòng Marketing', 'Phòng marketing và quảng bá'),
('Phòng Kỹ thuật', 'Phòng kỹ thuật và phát triển sản phẩm'),
('Phòng Nghiên cứu và Phát triển', 'Phòng R&D và đổi mới'),
('Phòng Mua hàng', 'Phòng mua hàng và chuỗi cung ứng'),
('Phòng Chất lượng', 'Phòng kiểm soát chất lượng'),
('Phòng Pháp chế', 'Phòng pháp chế và tuân thủ'),
('Phòng Hành chính', 'Phòng hành chính và văn phòng'),
('Phòng An ninh', 'Phòng an ninh và bảo vệ'),
('Kho', 'Quản lý kho và tồn kho'),
('Bảo trì', 'Phòng bảo trì và sửa chữa'),
('Khác', 'Các phòng ban khác');

-- Add foreign key constraint to users table (if not exists)
-- This will link users.department to departments.name
-- Note: Since users.department is VARCHAR, we'll keep it as text reference
-- The relationship will be maintained at application level
