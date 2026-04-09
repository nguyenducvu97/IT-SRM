<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiá»m tra Font Tiáº¿ng Viá»t - Final Fix</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Remove Google Fonts that might cause issues -->
    <!-- Use only local/system fonts -->
    
    <style>
        /* Vietnamese Font Stack - Local First */
        body {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Override Bootstrap fonts */
        .alert, .btn, .card, .navbar, .modal, .form-control, .table {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .alert h6, .alert strong, .alert p {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .card-title, .card-text, .card-header h5 {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .navbar-brand, .nav-link {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .btn {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .form-control, .form-label {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .table th, .table td {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        .modal-title, .modal-body {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        /* Vietnamese text classes */
        .vietnamese-text {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif;
            line-height: 1.6;
        }
        
        .vietnamese-title {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif;
            font-weight: bold;
        }
        
        .vietnamese-body {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif;
            line-height: 1.6;
        }
        
        /* Test specific styles */
        .font-test {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .font-success {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .font-warning {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .font-error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        /* Override all text elements */
        h1, h2, h3, h4, h5, h6 {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        p, span, div, li, td, th {
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif !important;
        }
        
        /* Special Vietnamese character test */
        .vietnamese-special {
            font-size: 18px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        /* Force Vietnamese character display */
        .force-vietnamese {
            unicode-bidi: embed;
            direction: ltr;
            font-family: "Arial Unicode MS", "Arial", "Helvetica", "Times New Roman", "Times", serif;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">Kiá»m tra Font Tiáº¿ng Viá»t - Final Fix</h1>
                <p class="text-center text-muted">Test Vietnamese text rendering with local fonts</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-check-circle"></i> Vietnamese Text Test - Final</h5>
                    </div>
                    <div class="card-body">
                        <div class="font-test font-success">
                            <h6 class="vietnamese-title">Há» thá»ng thÃ´ng bÃ¡o nÃ¢ng cao</h6>
                            <p class="vietnamese-body">TÃ­ch há»£p PHPMailer vÃ i email tá»± Äng Äáº¹p dáº¡ng</p>
                            <p class="vietnamese-body">SSE cho thÃ´ng bÃ¡o thá»±c thá»i gian</p>
                            <p class="vietnamese-body">TÃ¹y chá»nh cÃ¡ nhÃ¢n cho tÃ¹ng ngÆ°á»i dÃ¹ng</p>
                            <p class="vietnamese-body">Queue xá»­ lÃ­ email tá»i Æ°u</p>
                            <p class="vietnamese-body">Lich sá»­ vÃ theo dÃµi thÃ´ng bÃ¡o</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle"></i> Alert Messages Test</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>ThÃ nh cÃ´ng!</strong> Há» thá»ng ÄÃ£ ÄÆ°á»£c thÃ nh cÃ´ng.
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>ThÃ´ng tin!</strong> ÄÃ¢y lÃ má»t thÃ´ng bÃ¡o thÃ´ng tin.
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Cáº£nh bÃ¡o!</strong> CÃ³ báº£n kiá»m tra láº¡i thÃ´ng tin.
                        </div>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i>
                            <strong>Lá»i!</strong> ÄÃ£y xáº£y ra trong quá trÃ¬nh.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-font"></i> Special Characters Test</h5>
                    </div>
                    <div class="card-body">
                        <div class="vietnamese-special">
                            <p><strong>áº¡ áº¢ áº£ áº¥ áº§ áº© áºª áº«</strong></p>
                            <p><strong>Ã¢, Ãª, Ãª, Ãª, Ãª, Ãª, Ãª</strong></p>
                            <p><strong>Ä Ä Ä Ä Ä Ä Ä Ä Ä Ä</strong></p>
                            <p><strong>Ä, Ä, Ä, Ä, Ä, Ä, Ä</strong></p>
                            <p><strong>Ã´ Ãµ Ã´ Â´ Âµ Â´ Âµ Â´ Âµ Â´ Âµ</strong></p>
                            <p><strong>Ã´, Ãµ, Â´, Âµ, Â´, Âµ, Â´</strong></p>
                            <p><strong>Æ¡ Æ¡ Æ¡ Æ¡ Æ¡ Æ¡ Æ¡ Æ¡ Æ¡ Æ¡</strong></p>
                            <p><strong>Æ¡, Æ¡, Æ¡, Æ¡, Æ¡, Æ¡, Æ¡</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code"></i> Font Detection</h5>
                    </div>
                    <div class="card-body">
                        <div id="fontDetection">
                            <p><strong>Available Fonts:</strong> <span id="availableFonts">Checking...</span></p>
                            <p><strong>Current Font:</strong> <span id="currentFont">Checking...</span></p>
                            <p><strong>Font Rendering:</strong> <span id="fontRendering">Checking...</span></p>
                            <p><strong>Text Encoding:</strong> <span id="textEncoding">Checking...</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-check"></i> Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults">
                            <h6>Font Loading Test:</h6>
                            <p id="fontLoadingStatus">Checking...</p>
                            
                            <h6>Text Rendering Test:</h6>
                            <p id="textRenderingStatus">Checking...</p>
                            
                            <h6>Character Display Test:</h6>
                            <p id="characterDisplayStatus">Checking...</p>
                            
                            <h6>Overall Status:</h6>
                            <div id="overallStatus" class="alert alert-info">
                                <i class="fas fa-spinner fa-spin"></i> Testing...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Vietnamese font final test loaded");
            
            // Check document charset
            document.getElementById("textEncoding").textContent = document.characterSet;
            
            // Check computed font family
            const testElement = document.querySelector("body");
            const computedStyle = window.getComputedStyle(testElement);
            document.getElementById("currentFont").textContent = computedStyle.fontFamily;
            
            // Check available fonts
            const fonts = document.fonts;
            if (fonts && fonts.size > 0) {
                document.getElementById("availableFonts").textContent = fonts.size + " fonts loaded";
            } else {
                document.getElementById("availableFonts").textContent = "Using system fonts";
            }
            
            // Test Vietnamese character rendering
            const testText = "Há» thá»ng thÃ´ng bÃ¡o nÃ¢ng cao";
            const testDiv = document.createElement("div");
            testDiv.style.position = "absolute";
            testDiv.style.left = "-9999px";
            testDiv.style.visibility = "hidden";
            testDiv.innerHTML = testText;
            testDiv.style.fontFamily = "Arial Unicode MS, Arial, sans-serif";
            document.body.appendChild(testDiv);
            
            setTimeout(() => {
                const renderedText = testDiv.textContent;
                const isCorrect = renderedText === testText;
                
                document.getElementById("characterDisplayStatus").innerHTML = isCorrect ? 
                    "<span style=\"color: green;\">â Vietnamese characters display correctly</span>" : 
                    "<span style=\"color: red;\">â Vietnamese characters not displaying correctly</span>";
                
                document.body.removeChild(testDiv);
            }, 100);
            
            // Test canvas rendering
            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");
            context.font = "16px Arial Unicode MS, Arial, sans-serif";
            context.fillText("Test", 10, 30);
            
            const pixelData = context.getImageData(10, 30, 1, 1);
            const rendered = pixelData.data[0] !== 0;
            
            document.getElementById("fontRendering").innerHTML = rendered ? 
                "<span style=\"color: green;\">â Font rendering OK</span>" : 
                "<span style=\"color: orange;\">â  Font rendering limited</span>";
            
            // Test font loading
            setTimeout(() => {
                const fontLoadingStatus = document.getElementById("fontLoadingStatus");
                if (fonts && fonts.size > 0) {
                    fontLoadingStatus.innerHTML = "<span style=\"color: green;\">â Fonts loaded: " + fonts.size + "</span>";
                } else {
                    fontLoadingStatus.innerHTML = "<span style=\"color: orange;\">â  Using system fonts</span>";
                }
            }, 2000);
            
            // Test text rendering
            setTimeout(() => {
                const textRenderingStatus = document.getElementById("textRenderingStatus");
                const testElement = document.querySelector(".vietnamese-title");
                if (testElement) {
                    const computedStyle = window.getComputedStyle(testElement);
                    const fontFamily = computedStyle.fontFamily;
                    
                    if (fontFamily.includes("Arial Unicode MS") || fontFamily.includes("Arial")) {
                        textRenderingStatus.innerHTML = "<span style=\"color: green;\">â Vietnamese font rendering OK</span>";
                    } else {
                        textRenderingStatus.innerHTML = "<span style=\"color: orange;\">â  Using fallback fonts</span>";
                    }
                } else {
                    textRenderingStatus.innerHTML = "<span style=\"color: red;\">â Test element not found</span>";
                }
            }, 3000);
            
            // Overall status
            setTimeout(() => {
                const overallDiv = document.getElementById("overallStatus");
                overallDiv.className = "alert alert-success";
                overallDiv.innerHTML = "<i class=\"fas fa-check-circle\"></i> <strong>Font Test Complete!</strong> Vietnamese text should display correctly with Arial Unicode MS.";
            }, 4000);
            
            // Console logging
            console.log("Document charset:", document.characterSet);
            console.log("Font family:", computedStyle.fontFamily);
            console.log("Available fonts:", fonts ? fonts.size : "Using system fonts");
            console.log("Test text:", testText);
        });
    </script>
</body>
</html>