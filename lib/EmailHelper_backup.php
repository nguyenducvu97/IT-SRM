<?php
require_once __DIR__ . '/../autoloader.php';
// POP3 Email Helper for Company Email System
// Hỗ trợ POP3 cho công ty

class EmailHelper {
    public $mail;
    public $config;
    
    public function __construct() {
        // POP3 Configuration for company email
        $this->config = array(
            'protocol' => 'pop3',
            'host' => 'gw.sgitech.com.vn', // Company POP3 server
            'port' => 25, // SMTP port for sending emails
            'username' => 'ndvu@sgitech.com.vn', // Company email
            'password' => 'ndvu', // Company email password
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System',
            'encryption' => 'none', // POP3 typically no encryption
            'pop3_server' => 'gw.sgitech.com.vn',
            'smtp_server' => 'gw.sgitech.com.vn' // Company SMTP for sending
        );
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            // Method 1: Try company SMTP first
            if ($this->sendCompanySMTP($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_COMPANY_SMTP');
                return true;
            }
            
            // Method 2: Try PHP mail as fallback
            if ($this->sendPhpMail($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_PHPMAIL');
                return true;
            }
            
            // Method 3: Log for manual sending
            $this->logEmail($to, $subject, $body, 'FAILED');
            return false;
            
        } catch (Exception $e) {
            error_log("EmailHelper Error: " . $e->getMessage());
            $this->logEmail($to, $subject, $body, 'ERROR');
            return false;
        }
    }
    
    private function sendCompanySMTP($to, $toName, $subject, $body) {
        try {
            // Use PHPMailer with company SMTP
            $this->mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings for company SMTP
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_server'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Try TLS first
            $this->mail->Port = 25; // Company SMTP port
            
            // Try with TLS first
            try {
                $this->mail->send();
                return true;
            } catch (Exception $e) {
                // Fallback to no encryption
                $this->mail->SMTPSecure = '';
                $this->mail->Port = 25;
                $this->mail->send();
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Company SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendPhpMail($to, $toName, $subject, $body) {
        // Encode subject for UTF-8
        $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        );
        
        $header_string = implode("\r\n", $headers);
        $encoded_body = chunk_split(base64_encode($body));
        
        return @mail($to, $encoded_subject, $encoded_body, $header_string);
    }
    
    private function logEmail($to, $subject, $body, $status) {
        // Create email file for backup
        if ($status === 'FAILED' || $status === 'ERROR') {
            $email_content = "To: $to\n";
            $email_content .= "Subject: $subject\n";
            $email_content .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\n";
            $email_content .= "MIME-Version: 1.0\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\n\n";
            $email_content .= $body;
            
            $email_file = __DIR__ . '/../logs/email_' . date('Y-m-d_H-i-s') . '.eml';
            file_put_contents($email_file, $email_content);
        }
        
        // Log activity
        $log_entry = sprintf(
            "[%s] %s | To: %s | Subject: %s\n",
            date('Y-m-d H:i:s'),
            $status,
            $to,
            $subject
        );
        
        $log_file = __DIR__ . '/../logs/email_activity.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public function sendNewRequestNotification($request_data) {
        require_once __DIR__ . '/../config/database.php';
        
        // Get all admin and staff emails
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role IN ('admin', 'staff') AND status = 'active'");
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recipients)) {
            $this->logEmail('', 'No recipients found', '', 'FAILED: No admin/staff users found');
            return false;
        }
        
        // Create email template
        $subject = "🔔 Yêu cầu dịch vụ mới #" . $request_data['id'];
        
        $body = '<div class="mail-container" align="left" valign="top" style="padding-top:5px;vertical-align:top;" id="displayFrameTD">
		<iframe name="displayFrame" id="displayFrame" src="./?mode=display&amp;box=&amp;&amp;iid=194969&amp;yn_preview=" width="100%" height="751" frameborder="0" marginheight="0" onload="autoResize(this);">
			<html><head>
<meta content="text/html; charset=UTF-8" http-equiv="content-type">
<script>
var im_domain = "";
if(im_domain.length>0) document.domain=im_domain;
function onclick_alarm(sURL)
{
	if(sURL.indexOf("research") >0)
	{
		window.open(sURL, \'\', \'scrollbars=yes,toolbar=no,location=no,directories=no,width=1000,height=700,resizable=yes\');
		return;
	}

	try
	{
		if (top.opener)
		{
			top.opener.location.href  = sURL;
			parent.close();
		}
		else
		{
			if (is_frame_object(top) === false)
			{
				if (is_frame_object(parent) ===true)
				{
					parent.location.href = sURL;
				}
				else
				{
					location.href = sURL;
				}
			}
			else
			{
				top.location.href= sURL;
			}
		}
	}
	catch (e) 
	{
		if (is_frame_object(top) === false)
		{
			if (is_frame_object(parent) ===true)
			{
				parent.location.href = sURL;
			}
			else
			{
				location.href = sURL;
			}
		}
		else
		{
			top.location.href= sURL;
		}
	}
}

function onclick_sso_alarm(sURL,cpn_name)
{
	if(!confirm("["+cpn_name+"]"+" \u1ec1y\u1ec1n c\u1ef1 \u0111i chuy\u1ec3n.")) return;

	onclick_alarm(sURL);

}

function is_frame_object(obj)
{
	try
	{
		if (obj.name)	return true;
		else return false;
	}catch(err){
		return false;
	}
}

function autoResize(iframe)
{
	iframe.style.height = iframe.contentWindow.document.body.scrollHeight + "px";
}
</script>
<script src="/APF/js/jquery/js/jquery-1.7.1.min.js"></script><script>
function setFrameFontSize(a)
{
	var defaultFontSize = 1;//em
	var minFontSize = 1;//em
	var maxFontSize = 1.6;//em
	obj = document.getElementById("am_mail_content");//\u1ee5ng \u0111\u1ea1i thu nh\u1ecf
	var objFontSize = obj.style.fontSize;
	if (!objFontSize) { objFontSize = parseFloat(defaultFontSize)+"em"; }
	var checkFontSize = (Math.round(12*parseFloat(objFontSize))+(a*2))/12;
	if (checkFontSize >= maxFontSize) { checkFontSize = maxFontSize; obj.style.fontSize = checkFontSize+"em";  }
	else if (checkFontSize <= minFontSize) { checkFontSize = minFontSize; obj.style.fontSize = checkFontSize+"em"; }
	else { obj.style.fontSize = checkFontSize+"em"; }
	//alert(checkFontSize);
}


$(function(){$("body").keydown(function(event){
	if($("#dialog-paper-out",parent.document).is(\':visible\')) return;

	// \u01afu b\u1ea1n n\u1ed9i dung input , textarea nh\u1eadp v\u00e0o c\u1ea7n thi\u1ebft b\u1ea1ng ph\u00edm t\u1ea1t : BMS 16971
	var shortcutsCmd = true;
	$("body").find("input,textarea").each(function(){
	    if ( $(this).is(\':focus\') === true)	shortcutsCmd = false;
	});
	if (shortcutsCmd == false ) return;

	// \u0111\u1eb7c bi\u1ec7t s\u1eed d\u1ee5ng BMS:16633
	if(event.ctrlKey == false && event.altKey == false && event.shiftKey == false)
	{
		switch(event.which)
		{
			case 66: $("#btn_id_btn_block",parent.document).click();	break;		// b : block
			case 68: $("#btn_id_btn_del",parent.document).click();		break;		// d : delete
			case 70: $("#btn_id_btn_forward",parent.document).click();	break;		// f : forward
			case 72: parent.showHeader();						break;		// h : header toggle
			case 76: $("#btn_id_btn_list",parent.document).click();		break;		// l : list
			case 78: parent.viewNextArticle(\'n\');					break;		// n : next article
			case 80: parent.viewNextArticle(\'p\');					break;		// p : previous article
			case 82: $("#btn_id_btn_reply",parent.document).click();	break;		// r : reply
			case 83: $("#btn_id_btn_spam",parent.document).click();	break;		// s : spam
			default: 										break;
		}
	}
});});
</script>
<link rel="stylesheet" type="text/css" href="/data/com/CA/custom/css/style.mail.editor.css">
<style>
img{border:0px;}
a,a:link,a:visited,a:active{text-decoration:none;}a:hover{text-decoration:underline;}
</style>
</head>
<body marginheight="0">
<style>
.email-container {
    max-width: 600px;
    margin: 20px auto;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    font-family: Arial, sans-serif;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.email-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.email-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.email-header p {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.email-body {
    padding: 40px 30px;
    background-color: #fafbfc;
}

.email-title {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 25px;
    text-align: center;
    position: relative;
}

.email-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 2px;
}

.request-details {
    background: white;
    border: 1px solid #e8eaed;
    border-radius: 10px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.request-detail-row {
    display: flex;
    margin-bottom: 15px;
    align-items: flex-start;
}

.request-detail-row:last-child {
    margin-bottom: 0;
}

.request-label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
    flex-shrink: 0;
    font-size: 14px;
}

.request-value {
    color: #212529;
    flex: 1;
    font-size: 14px;
    word-break: break-word;
}

.priority-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-high {
    background: linear-gradient(135deg, #ff6b6b, #ff5252);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
}

.priority-medium {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.priority-low {
    background: linear-gradient(135deg, #4caf50, #388e3c);
    color: white;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.cta-button {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    letter-spacing: 0.5px;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.email-footer {
    background: #f8f9fa;
    padding: 30px;
    text-align: center;
    border-top: 1px solid #e8eaed;
}

.footer-text {
    margin: 5px 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.5;
}

.footer-text strong {
    color: #495057;
}

@media (max-width: 600px) {
    .email-container {
        margin: 10px;
        border-radius: 8px;
    }
    
    .email-header {
        padding: 30px 20px;
    }
    
    .email-header h1 {
        font-size: 24px;
    }
    
    .email-body {
        padding: 30px 20px;
    }
    
    .request-details {
        padding: 20px;
    }
    
    .request-detail-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .request-label {
        margin-bottom: 5px;
        min-width: auto;
    }
    
    .cta-button {
        width: 100%;
        padding: 12px 20px;
    }
}
</style>

<div class="email-container">
    <div class="email-header">
        <h1>IT Service Request</h1>
        <p>Hê thong yêu câu dich vu CNTT</p>
    </div>
    
    <div class="email-body">
        <h2 class="email-title">Yêu câu dich vu mõi</h2>
        
        <div class="request-details">
            <div class="request-detail-row">
                <span class="request-label">Mã yêu câu:</span>
                <span class="request-value"><strong>#' . $request_data['id'] . '</strong></span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Tiêu dê:</span>
                <span class="request-value">' . htmlspecialchars($request_data['title']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Nguôi tao:</span>
                <span class="request-value">' . htmlspecialchars($request_data['requester_name']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Danh mûc:</span>
                <span class="request-value">' . htmlspecialchars($request_data['category']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Uu tiên:</span>
                <span class="request-value"><span class="priority-badge priority-' . strtolower($request_data['priority']) . '">' . htmlspecialchars($request_data['priority']) . '</span></span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Mô tã:</span>
                <span class="request-value">' . nl2br(htmlspecialchars($request_data['description'])) . '</span>
            </div>
        </div>
                        
                <div style="text-align: center; margin: 30px 0;">
                    <a href="http://localhost/it-service-request/" class="cta-button" target="_blank">Xem chi tiêt yêu câu -></a>
                </div>
                
                <div class="email-footer">
                    <p class="footer-text"><strong>IT Service Request System</strong></p>
                    <p class="footer-text">Dây là email tu dông. Vui lòng không trá loi email này.</p>
                    <p class="footer-text">Nêu cân hõ trõ, vui lòng liên hê IT Department.</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body></html>
		</iframe>
	</div>';
        
$success_count = 0;
$total_count = count($recipients);
        
foreach ($recipients as $recipient) {
            if ($this->sendEmail($recipient['email'], $recipient['full_name'], $subject, $body)) {
                $success_count++;
            }
        }
        
        $this->logEmail('multiple', $subject, $body, "SENT to {$success_count}/{$total_count} admin/staff recipients");
        return $success_count > 0;
    }
}
?>
