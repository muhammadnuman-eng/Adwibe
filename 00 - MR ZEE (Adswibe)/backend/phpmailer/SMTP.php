<?php
/**
 * PHPMailer RFC821 SMTP email transport class.
 * Minimal bundled version for Adswibe® — upgrade via Composer for full features.
 * Full version: https://github.com/PHPMailer/PHPMailer
 */
namespace PHPMailer\PHPMailer;

class SMTP {
    const VERSION = '6.9.1';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;

    public $do_debug = 0;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 300;
    public $Timelimit = 300;
    public $smtp_transaction_id_patterns = [
        'exim' => '/[\d]{3} OK id=(.*)/',
        'sendmail' => '/[\d]{3} 2.0.0 (.*) Message/',
        'postfix' => '/[\d]{3} 2.0.0 Ok: queued as (.*)/',
        'Microsoft_ESMTP' => '/[0-9]{3} 2\.[\d]\.0 (.{10,})/',
        'Amazon_SES' => '/[\d]{3} Ok (.{10,})/',
        'SendGrid' => '/[\d]{3} Ok: queued as (.*)/',
        'CampaignMonitor' => '/[\d]{3} 2\.0\.0 OK:([a-zA-Z\d]{48})/',
        'Haraka' => '/[\d]{3} Message Queued \((.*)\)/',
        'MailHog' => '/[\d]{3} Ok MsgId:(.*)/',
        'MagicMail' => '/[\d]{3} Message queued as ([\d]{14}-[\d]{6})/',
    ];
    protected $smtp_transaction_id = '';
    protected $smtp_conn;
    protected $error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
    protected $helo_rply = null;
    protected $server_caps = null;
    protected $last_reply = '';

    public function connect($host, $port = null, $timeout = 30, $options = []) {
        static $streamok;
        if (is_null($streamok)) {
            $streamok = function_exists('stream_socket_client');
        }
        $this->setError('');
        if ($this->connected()) { $this->close(); }
        if (empty($port)) { $port = self::DEFAULT_PORT; }
        $this->edebug("Connection: opening to $host:$port, timeout=$timeout, options=" . (count($options) > 0 ? var_export($options, true) : 'array()'), self::DEBUG_CONNECTION);
        $errno = 0;
        $errstr = '';
        if ($streamok) {
            $socket_context = stream_context_create($options);
            $this->smtp_conn = stream_socket_client(
                $host . ':' . $port,
                $errno, $errstr, $timeout,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
        } else {
            $this->smtp_conn = fsockopen($host, $port, $errno, $errstr, $timeout);
        }
        if (!is_resource($this->smtp_conn)) {
            $this->setError('Failed to connect to server', '', $errno, "$errno $errstr");
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ": $errstr ($errno)", self::DEBUG_CLIENT);
            return false;
        }
        stream_set_timeout($this->smtp_conn, $timeout, 0);
        $announce = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $announce, self::DEBUG_SERVER);
        return true;
    }

    public function startTLS() {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) { return false; }
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return (bool) $crypto_ok;
    }

    public function authenticate($username, $password, $authtype = null, $OAuth = null) {
        if (!$this->server_caps) { $this->setError('Authentication is not allowed before HELO/EHLO'); return false; }
        if (array_key_exists('EHLO', $this->server_caps)) {
            if (!array_key_exists('AUTH', $this->server_caps)) { $this->setError('Authentication is not supported by server'); return false; }
            $this->server_caps['AUTH'];
        }
        if (is_null($authtype)) {
            foreach (['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'] as $method) {
                if (in_array($method, $this->server_caps['AUTH'])) { $authtype = $method; break; }
            }
            if (empty($authtype)) { $this->setError('No supported authentication methods found'); return false; }
        }
        $this->edebug('AUTH: '.$authtype, self::DEBUG_LOWLEVEL);
        switch ($authtype) {
            case 'PLAIN':
                if (!$this->sendCommand('AUTH', 'AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password), 235)) { return false; }
                break;
            case 'LOGIN':
                if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) { return false; }
                if (!$this->sendCommand('Username', base64_encode($username), 334)) { return false; }
                if (!$this->sendCommand('Password', base64_encode($password), 235)) { return false; }
                break;
            case 'CRAM-MD5':
                if (!$this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) { return false; }
                $challenge = base64_decode(substr($this->last_reply, 4));
                $response = $username . ' ' . $this->hmac($challenge, $password);
                if (!$this->sendCommand('Username', base64_encode($response), 235)) { return false; }
                break;
            default:
                $this->setError("Authentication method '$authtype' is not supported");
                return false;
        }
        return true;
    }

    protected function hmac($data, $key) {
        if (function_exists('hash_hmac')) { return hash_hmac('md5', $data, $key); }
        $bytelen = 64;
        if (strlen($key) > $bytelen) { $key = pack('H*', md5($key)); }
        $key = str_pad($key, $bytelen, chr(0x00));
        $ipad = str_pad('', $bytelen, chr(0x36));
        $opad = str_pad('', $bytelen, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
    }

    public function connected() {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) { $this->edebug('SMTP NOTICE: EOF caught while checking if connected', self::DEBUG_CLIENT); $this->close(); return false; }
            return true;
        }
        return false;
    }

    public function close() {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) { fclose($this->smtp_conn); $this->smtp_conn = null; $this->edebug('Connection: closed', self::DEBUG_CONNECTION); }
    }

    public function data($msg_data) {
        if (!$this->sendCommand('DATA', 'DATA', 354)) { return false; }
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));
        foreach ($lines as $line) {
            $lines_out = [];
            if (!empty($line) && $line[0] === '.') { $line = '.' . $line; }
            while (strlen($line) > self::MAX_LINE_LENGTH) {
                $pos = strrpos(substr($line, 0, self::MAX_LINE_LENGTH), ' ');
                if (!$pos) { $pos = self::MAX_LINE_LENGTH - 1; $lines_out[] = substr($line, 0, $pos); $line = substr($line, $pos); }
                else { $lines_out[] = substr($line, 0, $pos); $line = substr($line, $pos + 1); }
            }
            $lines_out[] = $line;
            foreach ($lines_out as $line_out) { if (!$this->client_send($line_out . static::CRLF)) { return false; } }
        }
        return $this->sendCommand('DATA END', '.', 250);
    }

    public function hello($host = '') {
        return (bool) ($this->sendHello('EHLO', $host) or $this->sendHello('HELO', $host));
    }

    protected function sendHello($hello, $host) {
        $noerror = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) { $this->parseHelloFields($hello); } else { $this->server_caps = null; }
        return $noerror;
    }

    protected function parseHelloFields($type) {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $n => $s) {
            $s = trim(substr($s, 4));
            if (!$s) { continue; }
            $fields = explode(' ', $s);
            if ($fields) { if (!$n) { $this->server_caps['HELO'] = $s; } else {
                $name = array_shift($fields); $fields = array_pop($fields) ? $fields : [];
                $this->server_caps[$name] = $fields; }
            }
        }
    }

    public function mail($from) { return $this->sendCommand('MAIL FROM', 'MAIL FROM:<' . $from . '>', 250); }
    public function quit($close_on_error = true) {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $err = $this->error;
        if ($noerror || $close_on_error) { $this->close(); $this->error = $err; }
        return $noerror;
    }
    public function recipient($address, $dsn = '') {
        if (empty($dsn)) { $rcpt = 'RCPT TO:<' . $address . '>'; }
        else { $rcpt = 'RCPT TO:<' . $address . '> NOTIFY=' . $dsn; }
        return $this->sendCommand('RCPT TO', $rcpt, [250, 251]);
    }
    public function reset() { return $this->sendCommand('RSET', 'RSET', 250); }

    protected function sendCommand($command, $commandstring, $expect) {
        if (!$this->connected()) { $this->setError("Called $command without being connected"); return false; }
        $commandstring = static::stripControlCharacters($commandstring);
        if (!$this->client_send($commandstring . static::CRLF, $command)) { return false; }
        $this->last_reply = $this->get_lines();
        $matches = [];
        if (preg_match('/^(\d{3})[ -]/m', $this->last_reply, $matches)) {
            $code = (int) $matches[1];
        } else {
            $this->setError("Invalid response code received from server", $this->last_reply);
            return false;
        }
        $code_ex = (preg_match_all('/^\d{3}[ -](.*)$/m', $this->last_reply, $matches)) ? implode("\n", $matches[1]) : null;
        if (!in_array($code, (array) $expect)) {
            $this->setError("$command command failed", $code_ex, (string) $code);
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ': ' . $this->last_reply, self::DEBUG_CLIENT);
            return false;
        }
        $this->setError('');
        return true;
    }

    public function sendAndMail($from) { return $this->sendCommand('SAML', 'SAML FROM:<' . $from . '>', 250); }
    public function verify($name) { return $this->sendCommand('VRFY', 'VRFY ' . $name, [250, 251]); }
    public function noop() { return $this->sendCommand('NOOP', 'NOOP', 250); }
    public function turn() { $this->setError('This method, TURN, of the SMTP is not implemented'); $this->edebug('SMTP NOTICE: ' . $this->error['error'], self::DEBUG_CLIENT); return false; }

    public function client_send($data, $command = '') {
        $this->edebug("CLIENT -> SERVER: $data", self::DEBUG_CLIENT);
        set_error_handler([$this, 'errorHandler']);
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();
        return $result;
    }

    public function getError() { return $this->error; }
    public function getServerExtList() { return $this->server_caps; }
    public function getServerExt($name) { if (!$this->server_caps) { $this->setError('No HELO/EHLO was sent'); return null; } return array_key_exists($name, $this->server_caps) ? $this->server_caps[$name] : null; }
    public function getLastReply() { return $this->last_reply; }

    protected function get_lines() {
        if (!is_resource($this->smtp_conn)) { return ''; }
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) { $endtime = time() + $this->Timelimit; }
        $selR = [$this->smtp_conn]; $selW = null;
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            set_error_handler([$this, 'errorHandler']);
            $n = stream_select($selR, $selW, $selW, $this->Timelimit);
            restore_error_handler();
            if ($n === false) { break; }
            $str = fgets($this->smtp_conn, 515);
            $this->edebug("SERVER -> CLIENT: $str", self::DEBUG_SERVER);
            $data .= $str;
            if ((isset($str[3]) && $str[3] === ' ')) { break; }
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) { $this->edebug('SMTP NOTICE: SMTP timeout', self::DEBUG_CLIENT); break; }
            if ($endtime && time() > $endtime) { break; }
        }
        return $data;
    }

    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '') {
        $this->error = ['error' => $message, 'detail' => $detail, 'smtp_code' => $smtp_code, 'smtp_code_ex' => $smtp_code_ex];
    }

    public static function stripControlCharacters($string) {
        return (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }

    protected function edebug($str, $level = 0) {
        if ($level > $this->do_debug) { return; }
        if (is_callable($this->Debugoutput) && !in_array($this->Debugoutput, ['error_log', 'html', 'echo'])) {
            call_user_func($this->Debugoutput, $str, $level); return;
        }
        switch ($this->Debugoutput) {
            case 'error_log': error_log($str); break;
            case 'html': echo gmdate('Y-m-d H:i:s') . ' ' . htmlentities($str, ENT_QUOTES | ENT_SUBSTITUTE) . "<br>\n"; break;
            case 'echo': default:
                $str = preg_replace('/[\r\n]+/', '', $str);
                echo gmdate('Y-m-d H:i:s') . ' ' . $str . "\n";
        }
    }

    public function errorHandler($errno, $errmsg, $errfile = '', $errline = 0) {
        $notice = 'Connection failed.';
        $this->setError($notice, $errno, "$errmsg");
        $this->edebug("$notice Error #$errno: $errmsg [$errfile line $errline]", self::DEBUG_CONNECTION);
    }

    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;

    public function getDebugOutput() { return $this->Debugoutput; }
    public function setDebugOutput($method = 'echo') { $this->Debugoutput = $method; }
    public function getDebugLevel() { return $this->do_debug; }
    public function setDebugLevel($level = 0) { $this->do_debug = $level; }
    public function getTimeout() { return $this->Timeout; }
    public function setTimeout($timeout = 300) { $this->Timeout = $timeout; }
    public function getSMTPTransactionID() { return $this->smtp_transaction_id; }
}
