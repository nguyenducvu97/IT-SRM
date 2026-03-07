// Department Helper Functions
class DepartmentHelper {
    // Cache departments from database
    static departments = [];
    static loaded = false;

    // Load departments from database
    static async loadDepartments() {
        if (this.loaded) return this.departments;
        
        console.log('🔍 Loading departments...');
        
        try {
            const response = await fetch('api/departments.php?action=dropdown');
            console.log('📡 API response status:', response.status);
            
            const data = await response.json();
            console.log('📋 API Response:', data);
            
            if (data.success) {
                this.departments = data.data;
                this.loaded = true;
                console.log('✅ Departments loaded:', this.departments.length);
                return this.departments;
            } else {
                console.error('❌ Failed to load departments:', data.message);
                // Fallback to default departments
                return this.getDefaultDepartments();
            }
        } catch (error) {
            console.error('❌ Error loading departments:', error);
            // Fallback to default departments
            return this.getDefaultDepartments();
        }
    }

    // Get default departments (fallback)
    static getDefaultDepartments() {
        return [
            'Ban Giám đốc',
            'Phòng Kế hoạch',
            'Phòng Tài chính - Kế toán',
            'Phòng Nhân sự',
            'Phòng Kinh doanh',
            'Phòng Marketing',
            'Phòng Kỹ thuật',
            'Phòng Nghiên cứu và Phát triển',
            'Phòng Mua hàng',
            'Phòng Chất lượng',
            'Phòng Pháp chế',
            'Phòng Hành chính',
            'Phòng An ninh',
            'Kho',
            'Bảo trì',
            'Khác'
        ];
    }

    // Get departments list (async)
    static async getDepartments() {
        if (!this.loaded) {
            await this.loadDepartments();
        }
        return this.departments;
    }

    // Get departments list (sync - for immediate use)
    static getDepartmentsSync() {
        return this.departments.length > 0 ? this.departments : this.getDefaultDepartments();
    }

    // Set department dropdown value
    static setDepartmentValue(selectElement, departmentValue) {
        if (!selectElement) return;
        
        // Clear current selection
        selectElement.value = '';
        
        // Find and set the matching option
        for (let i = 0; i < selectElement.options.length; i++) {
            if (selectElement.options[i].value === departmentValue) {
                selectElement.selectedIndex = i;
                break;
            }
        }
    }

    // Initialize all department dropdowns on the page
    static async initializeDepartmentDropdowns() {
        console.log('🔍 Initializing department dropdowns...');
        const departmentSelects = document.querySelectorAll('select[name="department"]');
        console.log('📋 Found department selects:', departmentSelects.length);
        
        // Load departments from database
        const departments = await this.getDepartments();
        console.log('📋 Departments loaded:', departments.length);
        
        departmentSelects.forEach((select, index) => {
            console.log(`🔄 Processing select ${index + 1}/${departmentSelects.length}`);
            
            // Store current value
            const currentValue = select.value;
            
            // Clear and populate options
            select.innerHTML = '<option value="">Chọn phòng ban</option>';
            
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                select.appendChild(option);
            });
            
            console.log(`✅ Populated select ${index + 1} with ${departments.length} departments`);
            
            // Restore previous value if it exists
            if (currentValue) {
                this.setDepartmentValue(select, currentValue);
            }
        });
        
        console.log('✅ All department dropdowns initialized');
    }

    // Initialize dropdowns synchronously (fallback)
    static initializeDepartmentDropdownsSync() {
        console.log('🔄 Initializing department dropdowns synchronously...');
        const departmentSelects = document.querySelectorAll('select[name="department"]');
        const departments = this.getDepartmentsSync();
        console.log('📋 Sync departments loaded:', departments.length);
        
        departmentSelects.forEach((select, index) => {
            console.log(`🔄 Processing select ${index + 1}/${departmentSelects.length} (sync)`);
            
            // If dropdown doesn't have options, populate it
            if (select.options.length <= 1) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Chọn phòng ban</option>';
                
                departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    select.appendChild(option);
                });
                
                console.log(`✅ Populated select ${index + 1} with ${departments.length} departments (sync)`);
                
                // Restore previous value if it exists
                if (currentValue) {
                    this.setDepartmentValue(select, currentValue);
                }
            } else {
                console.log(`⏭️ Select ${index + 1} already has options, skipping`);
            }
        });
        
        console.log('✅ All department dropdowns initialized (sync)');
    }

    // Refresh departments from database
    static async refreshDepartments() {
        this.loaded = false;
        this.departments = [];
        return await this.loadDepartments();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM loaded, initializing department dropdowns...');
    
    // Try async initialization first
    DepartmentHelper.initializeDepartmentDropdowns().catch((error) => {
        console.error('❌ Async initialization failed:', error);
        console.log('🔄 Falling back to sync initialization...');
        // Fallback to sync initialization
        DepartmentHelper.initializeDepartmentDropdownsSync();
    });
});
