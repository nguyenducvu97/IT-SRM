<?php
// Simple PHPMailer implementation for basic email functionality
if (!class_exists('PHPMailer')) {
class PHPMailer {
    public $Mailer = 'smtp';
    public $Host = 'localhost';
    public $Port = 25;
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $From = 'root@localhost';
    public $FromName = 'Root User';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $CharSet = 'UTF-8';
    public $Encoding = '8bit';
    public $ErrorInfo = '';
    public $SMTPDebug = 0;
    public $exceptions = false;
    public $Timeout = 10;
    
    protected $smtp;
    
    private $to = array();
    private $cc = array();
    private $bcc = array();
    private $ReplyTo = array();
    private $attachment = array();
    private $CustomHeader = array();
    private $message_type = '';
    private $boundary = array();
    
    public function __construct($exceptions = false) {
        $this->exceptions = (boolean)$exceptions;
    }
    
    public function isSMTP() {
        $this->Mailer = 'smtp';
    }
    
    public function setFrom($address, $name = '', $auto = true) {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }
    
    public function addAddress($address, $name = '') {
        $this->to[] = array($address, $name);
        return true;
    }
    
    public function addCC($address, $name = '') {
        $this->cc[] = array($address, $name);
        return true;
    }
    
    public function addBCC($address, $name = '') {
        $this->bcc[] = array($address, $name);
        return true;
    }
    
    public function addReplyTo($address, $name = '') {
        $this->ReplyTo[] = array($address, $name);
        return true;
    }
    
    public function isHTML($isHtml = true) {
        if ($isHtml) {
            $this->ContentType = 'text/html';
        } else {
            $this->ContentType = 'text/plain';
        }
    }
    
    public function send() {
        try {
            if ($this->Mailer == 'smtp') {
                return $this->smtpSend();
            } else {
                return $this->mailSend();
            }
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }
    
    private function smtpSend() {
        try {
            // Connect to SMTP server
            $this->smtp = new SMTP();
            
            if (!$this->smtp->connect($this->Host, $this->Port, $this->Timeout)) {
                throw new Exception('SMTP connect failed: ' . $this->smtp->getError());
            }
            
            // Say hello
            $this->smtp->hello();
            
            // Try STARTTLS if encryption is enabled
            if ($this->SMTPSecure == 'tls') {
                if (!$this->smtp->startTLS()) {
                    throw new Exception('STARTTLS failed');
                }
                // Say hello again after TLS
                $this->smtp->hello();
            }
            
            // Authenticate if required
            if ($this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                    throw new Exception('SMTP authenticate failed: ' . $this->smtp->getError());
                }
            }
            
            // Set from
            $this->smtp->mail($this->From);
            
            // Add recipients
            foreach ($this->to as $to) {
                $this->smtp->recipient($to[0]);
            }
            
            // Send email content
            $headers = $this->createHeaders();
            $body = $this->Body;
            
            $email_content = $headers . "\r\n\r\n" . $body;
            $this->smtp->data($email_content);
            
            // Close connection
            $this->smtp->quit();
            $this->smtp->close();
            
            return true;
            
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }
    
    private function mailSend() {
        $headers = $this->createHeaders();
        $to = $this->formatAddresses($this->to);
        
        return mail($to, $this->Subject, $this->Body, $headers);
    }
    
    private function createHeaders() {
        $headers = array();
        
        // From header
        $headers[] = 'From: ' . $this->formatAddress($this->From, $this->FromName);
        
        // Reply-To header
        if (!empty($this->ReplyTo)) {
            $headers[] = 'Reply-To: ' . $this->formatAddresses($this->ReplyTo);
        }
        
        // CC header
        if (!empty($this->cc)) {
            $headers[] = 'Cc: ' . $this->formatAddresses($this->cc);
        }
        
        // BCC header
        if (!empty($this->bcc)) {
            $headers[] = 'Bcc: ' . $this->formatAddresses($this->bcc);
        }
        
        // Content-Type header
        if (isset($this->ContentType)) {
            $headers[] = 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet;
        } else {
            $headers[] = 'Content-Type: text/plain; charset=' . $this->CharSet;
        }
        
        // MIME-Version
        $headers[] = 'MIME-Version: 1.0';
        
        // Content-Transfer-Encoding
        $headers[] = 'Content-Transfer-Encoding: ' . $this->Encoding;
        
        // X-Mailer
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        return implode("\r\n", $headers);
    }
    
    private function formatAddresses($addresses) {
        $formatted = array();
        foreach ($addresses as $address) {
            $formatted[] = $this->formatAddress($address[0], $address[1]);
        }
        return implode(', ', $formatted);
    }
    
    private function formatAddress($address, $name = '') {
        if (empty($name)) {
            return $address;
        }
        return '"' . addslashes($name) . '" <' . $address . '>';
    }
    
    public function clearAddresses() {
        $this->to = array();
    }
    
    public function clearCCs() {
        $this->cc = array();
    }
    
    public function clearBCCs() {
        $this->bcc = array();
    }
    
    public function clearReplyTos() {
        $this->ReplyTo = array();
    }
    
    public function clearAllRecipients() {
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
    }
    
    public function getError() {
        return $this->ErrorInfo;
    }
    
    public static function validateAddress($address) {
        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }
}
}

if (!class_exists('SMTP')) {
class SMTP {
    const VERSION = '5.2.27';
    const CRLF = "\r\n";
    const DEFAULT_SMTP_PORT = 25;
    
    public $Version = '5.2.27';
    public $SMTP_PORT = 25;
    public $CRLF = "\r\n";
    public $do_debug = 0;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 10;
    public $Timelimit = 30;
    
    protected $smtp_conn;
    protected $error = '';
    protected $helo_rply = '';
    protected $server_caps;
    protected $last_reply = '';
    
    public function __construct() {
        $this->smtp_conn = null;
        $this->error = null;
        $this->helo_rply = null;
        $this->server_caps = null;
        $this->last_reply = null;
    }
    
    public function connect($host, $port = null, $timeout = 30, $options = array()) {
        $this->host = $host;
        $this->port = $port ?: 25;
        
        // Create socket connection
        $this->smtp_conn = @fsockopen($host, $this->port, $errno, $errstr, $timeout);
        
        if (!$this->smtp_conn) {
            $this->error = "Failed to connect to $host:$port - $errstr ($errno)";
            return false;
        }
        
        // Read greeting
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return true;
    }
    
    public function hello($host = '') {
        if (!$this->smtp_conn) return false;
        
        $hello = $host ?: 'localhost';
        fputs($this->smtp_conn, "EHLO $hello\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return true;
    }
    
    public function authenticate($username, $password, $authtype = 'LOGIN') {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "AUTH LOGIN\r\n");
        $response = fgets($this->smtp_conn, 515);
        
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send username
        fputs($this->smtp_conn, base64_encode($username) . "\r\n");
        $response = fgets($this->smtp_conn, 515);
        
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send password
        fputs($this->smtp_conn, base64_encode($password) . "\r\n");
        $response = fgets($this->smtp_conn, 515);
        
        return substr($response, 0, 3) == '235';
    }
    
    public function mail($from) {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "MAIL FROM:<$from>\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return substr($this->last_reply, 0, 3) == '250';
    }
    
    public function recipient($to) {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "RCPT TO:<$to>\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return substr($this->last_reply, 0, 3) == '250';
    }
    
    public function data($data) {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "DATA\r\n");
        $response = fgets($this->smtp_conn, 515);
        
        if (substr($response, 0, 3) != '354') {
            return false;
        }
        
        // Send data
        $data = str_replace("\r\n.", "\r\n..", $data);
        fputs($this->smtp_conn, $data . "\r\n.\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return substr($this->last_reply, 0, 3) == '250';
    }
    
    public function quit() {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "QUIT\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return true;
    }
    
    public function close() {
        if ($this->smtp_conn) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
        return true;
    }
    
    public function startTLS() {
        if (!$this->smtp_conn) return false;
        
        // Send STARTTLS command
        fputs($this->smtp_conn, "STARTTLS\r\n");
        $response = fgets($this->smtp_conn, 515);
        
        if (substr($response, 0, 3) != '220') {
            $this->error = "STARTTLS failed: " . trim($response);
            return false;
        }
        
        // Enable TLS encryption
        if (function_exists('stream_socket_enable_crypto')) {
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            
            $crypto_enabled = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
            if (!$crypto_enabled) {
                $this->error = "TLS encryption failed";
                return false;
            }
            
            return true;
        } else {
            $this->error = "TLS not supported";
            return false;
        }
    }
    
    public function connected() {
        return $this->smtp_conn !== null;
    }
    
    public function reset() {
        if (!$this->smtp_conn) return false;
        
        fputs($this->smtp_conn, "RSET\r\n");
        $this->last_reply = fgets($this->smtp_conn, 515);
        
        return true;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getLastReply() {
        return $this->last_reply;
    }
    
    public function setDebugLevel($level = 0) {
        $this->do_debug = $level;
    }
    
    public function setTimeout($timeout = 0) {
        $this->Timeout = $timeout;
    }
    
    const DEBUG_OFF = 0;
    const DEBUG_SERVER = 1;
    const DEBUG_CLIENT = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;
}
}

if (!class_exists('phpmailerException')) {
class phpmailerException extends Exception {
    public function errorMessage() {
        $errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
        return $errorMsg;
    }
}
}
?>
