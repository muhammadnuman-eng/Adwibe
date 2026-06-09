<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Bundled version for Adswibe®.
 * Full: https://github.com/PHPMailer/PHPMailer
 */
namespace PHPMailer\PHPMailer;

class PHPMailer {
    const VERSION = '6.9.1';
    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;
    const CRLF = "\r\n";
    const MAX_LINE_LENGTH = 998;
    const STD_LINE_LENGTH = 76;
    const CHARSET_UTF8 = 'utf-8';
    const CHARSET_ASCII = 'us-ascii';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    public $Priority;
    public $CharSet = 'utf-8';
    public $ContentType = 'text/plain';
    public $Encoding = '8bit';
    public $ErrorInfo = '';
    public $From = 'root@localhost';
    public $FromName = 'Root User';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $Ical = '';
    public $Mailer = 'mail';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $SMTPAuth = false;
    public $SMTPOptions = [];
    public $Username = '';
    public $Password = '';
    public $AuthType = '';
    public $Timeout = 300;
    public $SMTPKeepAlive = false;
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SingleTo = false;
    public $do_verp = false;
    public $SingleToArray = [];
    public $XMailer = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Hostname = '';
    public $ConfirmReadingTo = '';
    public $WordWrap = 0;
    public $dsn = '';
    public $action_function = '';
    protected $smtp;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $RecipientsQueue = [];
    protected $ReplyToQueue = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary = [];
    protected $language = [];
    protected $error_count = 0;
    protected $exceptions = false;
    protected $uniqueid = '';
    protected $MIMEBody = '';
    protected $MIMEHeader = '';
    protected $mailHeader = '';
    protected $oauth;

    public function __construct($exceptions = null) {
        if ($exceptions !== null) { $this->exceptions = (bool)$exceptions; }
    }
    public function __destruct() { $this->smtpClose(); }

    public function isHTML($isHtml = true) {
        $this->ContentType = $isHtml ? static::CONTENT_TYPE_TEXT_HTML : static::CONTENT_TYPE_PLAINTEXT;
    }
    public function isSMTP() { $this->Mailer = 'smtp'; }
    public function isMail() { $this->Mailer = 'mail'; }

    public function addAddress($address, $name = '') { return $this->addAnAddress('to', $address, $name); }
    public function addCC($address, $name = '') { return $this->addAnAddress('cc', $address, $name); }
    public function addBCC($address, $name = '') { return $this->addAnAddress('bcc', $address, $name); }
    public function addReplyTo($address, $name = '') { return $this->addAnAddress('Reply-To', $address, $name); }

    protected function addAnAddress($kind, $address, $name = '') {
        $address = trim($address); $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (!static::validateAddress($address)) {
            $this->setError('Invalid address: '.$address);
            if ($this->exceptions) throw new Exception('Invalid address: '.$address);
            return false;
        }
        if ('Reply-To' === $kind) {
            if (!array_key_exists(strtolower($address), $this->ReplyTo)) {
                $this->ReplyTo[strtolower($address)] = [$address, $name]; return true;
            }
        } else {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                $this->{$kind}[] = [$address, $name];
                $this->all_recipients[strtolower($address)] = true; return true;
            }
        }
        return false;
    }

    public function setFrom($address, $name = '', $auto = true) {
        $address = trim($address); $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (!static::validateAddress($address)) {
            $this->setError('Invalid From address: '.$address);
            if ($this->exceptions) throw new Exception('Invalid From address: '.$address);
            return false;
        }
        $this->From = $address; $this->FromName = $name;
        if ($auto && empty($this->Sender)) $this->Sender = $address;
        return true;
    }

    public static function validateAddress($address, $patternselect = null) {
        return (bool)filter_var($address, FILTER_VALIDATE_EMAIL);
    }

    public function send() {
        try {
            if (!$this->preSend()) return false;
            return $this->postSend();
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) throw $exc;
            return false;
        }
    }

    public function preSend() {
        try {
            $this->error_count = 0;
            $this->mailHeader = '';
            if (count($this->to) + count($this->cc) + count($this->bcc) < 1) {
                throw new Exception('You must provide at least one recipient.', self::STOP_CRITICAL);
            }
            if (!empty($this->AltBody)) $this->ContentType = static::CONTENT_TYPE_MULTIPART_ALTERNATIVE;
            $this->setMessageType();
            if ($this->MessageDate === '') $this->MessageDate = static::rfcDate();
            $this->uniqueid = static::generateId();
            for ($i = 0; $i < 4; ++$i) $this->boundary[$i] = 'b'.$this->uniqueid.$i;
            $this->MIMEHeader = $this->createHeader();
            $this->MIMEBody = $this->createBody();
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) throw $exc;
            return false;
        }
        return true;
    }

    public function postSend() {
        try {
            switch ($this->Mailer) {
                case 'smtp': return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                case 'mail': return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
                case 'sendmail': return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
                default: return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) throw $exc;
        }
        return false;
    }

    protected function mailSend($header, $body) {
        $to = []; foreach ($this->to as $t) $to[] = $this->addrFormat($t);
        $toStr = implode(', ', $to);
        $params = (!empty($this->Sender) && static::isShellSafe($this->Sender)) ? '-f'.$this->Sender : null;
        $result = $this->mailPassthru($toStr, $this->Subject, $body, $header, $params);
        if (!$result) throw new Exception('mail() failed', self::STOP_CRITICAL);
        return true;
    }

    private function mailPassthru($to, $subject, $body, $header, $params) {
        $subj = $this->encodeHeader($this->secureHeader($subject));
        if (ini_get('safe_mode') || !$this->UseSendmailOptions || $params === null) {
            return @mail($to, $subj, $body, $header);
        }
        return @mail($to, $subj, $body, $header, $params);
    }

    protected function sendmailSend($header, $body) {
        $sendmail = sprintf('%s -oi -t', escapeshellcmd($this->Sendmail));
        $mail = @popen($sendmail, 'w');
        if (!$mail) throw new Exception('Could not execute sendmail', self::STOP_CRITICAL);
        fprintf($mail, '%s', $header); fprintf($mail, '%s', $body);
        $result = pclose($mail);
        if ($result !== 0) throw new Exception('sendmail failed', self::STOP_CRITICAL);
        return true;
    }

    public function getSMTPInstance() {
        if (!is_object($this->smtp)) $this->smtp = new SMTP();
        return $this->smtp;
    }

    protected function smtpConnect($options = []) {
        if (null === $this->smtp) $this->smtp = $this->getSMTPInstance();
        if ($this->smtp->connected()) return true;
        $this->smtp->setTimeout($this->Timeout);
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput);
        $hosts = explode(';', $this->Host);
        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (!preg_match('/^(?:(ssl|tls):\/\/)?(.+?)(?::(\d+))?$/', trim($hostentry), $hostinfo)) continue;
            $prefix = ''; $tls = (static::ENCRYPTION_STARTTLS === $this->SMTPSecure);
            if ('ssl' === $hostinfo[1] || static::ENCRYPTION_SMTPS === $this->SMTPSecure) { $prefix = 'ssl://'; $tls = false; }
            elseif ('tls' === $hostinfo[1]) $tls = true;
            $host = $hostinfo[2];
            $port = $this->Port;
            if (!empty($hostinfo[3])) $port = (int)$hostinfo[3];
            if ($this->smtp->connect($prefix.$host, $port, $this->Timeout, $options)) {
                try {
                    $hello = $this->Helo ?: ($this->Hostname ?: $this->serverHostname());
                    $this->smtp->hello($hello);
                    if ($this->SMTPAutoTLS && empty($prefix) && empty($this->SMTPSecure)) {
                        $ext = $this->smtp->getServerExtList();
                        if ($ext && array_key_exists('STARTTLS', $ext)) $tls = true;
                    }
                    if ($tls) { if (!$this->smtp->startTLS()) throw new Exception('STARTTLS failed'); $this->smtp->hello($hello); }
                    if ($this->SMTPAuth) {
                        if (!$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType))
                            throw new Exception('SMTP authenticate failed');
                    }
                    return true;
                } catch (Exception $exc) { $this->smtp->quit(); continue; }
            }
        }
        $this->smtp->close();
        throw new Exception('SMTP connect() failed.');
    }

    protected function smtpSend($header, $body) {
        if (!$this->smtpConnect($this->SMTPOptions)) throw new Exception('SMTP connect failed', self::STOP_CRITICAL);
        $smtp_from = (!empty($this->Sender) && static::validateAddress($this->Sender)) ? $this->Sender : $this->From;
        if (!$this->smtp->mail($smtp_from)) { $this->setError('FROM failed: '.$smtp_from); throw new Exception($this->ErrorInfo, self::STOP_CRITICAL); }
        $bad = [];
        foreach (array_merge($this->to, $this->cc, $this->bcc) as $t) {
            if (!$this->smtp->recipient($t[0])) $bad[] = $t[0];
        }
        if (count($bad) === count($this->all_recipients)) throw new Exception('All recipients failed', self::STOP_CRITICAL);
        if (!$this->smtp->data($header.$body)) throw new Exception('DATA not accepted', self::STOP_CRITICAL);
        $this->lastMessageID = '';
        if (!$this->SMTPKeepAlive) $this->smtp->quit();
        return true;
    }

    public function smtpClose() { if ($this->smtp && $this->smtp->connected()) { $this->smtp->quit(); $this->smtp->close(); } }

    protected function setMessageType() {
        $type = [];
        if ($this->alternativeExists()) $type[] = 'alt';
        if ($this->attachmentExists()) $type[] = 'attach';
        $this->message_type = $type ? implode('_', $type) : 'plain';
    }

    public function createHeader() {
        $r = '';
        $r .= $this->headerLine('Date', $this->MessageDate ?: static::rfcDate());
        if (count($this->to)) $r .= $this->addrAppend('To', $this->to);
        elseif (!count($this->cc)) $r .= $this->headerLine('To', 'undisclosed-recipients:;');
        $r .= $this->addrAppend('From', [[$this->From, $this->FromName]]);
        if (count($this->cc)) $r .= $this->addrAppend('Cc', $this->cc);
        if ('mail' !== $this->Mailer && count($this->bcc)) $r .= $this->addrAppend('Bcc', $this->bcc);
        if (count($this->ReplyTo)) $r .= $this->addrAppend('Reply-To', $this->ReplyTo);
        if ('mail' !== $this->Mailer) $r .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader($this->Subject)));
        $msgid = $this->MessageID ?: sprintf('<%s@%s>', $this->uniqueid, $this->serverHostname());
        $this->lastMessageID = $msgid;
        $r .= $this->headerLine('Message-ID', $msgid);
        if ($this->Priority) $r .= $this->headerLine('X-Priority', $this->Priority);
        if ($this->XMailer !== '') { if ($this->XMailer) $r .= $this->headerLine('X-Mailer', trim($this->XMailer)); }
        else $r .= $this->headerLine('X-Mailer', 'PHPMailer '.self::VERSION.' (https://github.com/PHPMailer/PHPMailer)');
        foreach ($this->CustomHeader as $h) $r .= $this->headerLine(trim($h[0]), $this->encodeHeader(trim($h[1])));
        $r .= $this->headerLine('MIME-Version', '1.0');
        switch ($this->message_type) {
            case 'alt': $r .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE.';'.static::CRLF.' boundary="'.$this->boundary[1].'"'); break;
            case 'attach':
            case 'alt_attach': $r .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_MIXED.';'.static::CRLF.' boundary="'.$this->boundary[1].'"'); break;
            default: $r .= 'Content-Type: '.$this->ContentType.'; charset='.$this->CharSet.static::CRLF; $r .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding); break;
        }
        return $r;
    }

    public function createBody() {
        $body = '';
        switch ($this->message_type) {
            case 'alt':
                $body .= $this->getBoundary($this->boundary[1], $this->CharSet, static::CONTENT_TYPE_PLAINTEXT, $this->Encoding);
                $body .= $this->encodeString($this->AltBody, $this->Encoding).static::CRLF;
                $body .= $this->getBoundary($this->boundary[1], $this->CharSet, static::CONTENT_TYPE_TEXT_HTML, $this->Encoding);
                $body .= $this->encodeString($this->Body, $this->Encoding).static::CRLF;
                $body .= '--'.$this->boundary[1].'--'.static::CRLF;
                break;
            case 'attach':
                $body .= $this->getBoundary($this->boundary[1], $this->CharSet, '', $this->Encoding);
                $body .= $this->encodeString($this->Body, $this->Encoding).static::CRLF;
                $body .= '--'.$this->boundary[1].'--'.static::CRLF;
                break;
            case 'alt_attach':
                $body .= '--'.$this->boundary[1].static::CRLF;
                $body .= 'Content-Type: '.static::CONTENT_TYPE_MULTIPART_ALTERNATIVE.';'.static::CRLF.' boundary="'.$this->boundary[2].'"'.static::CRLF.static::CRLF;
                $body .= $this->getBoundary($this->boundary[2], $this->CharSet, static::CONTENT_TYPE_PLAINTEXT, $this->Encoding);
                $body .= $this->encodeString($this->AltBody, $this->Encoding).static::CRLF;
                $body .= $this->getBoundary($this->boundary[2], $this->CharSet, static::CONTENT_TYPE_TEXT_HTML, $this->Encoding);
                $body .= $this->encodeString($this->Body, $this->Encoding).static::CRLF;
                $body .= '--'.$this->boundary[2].'--'.static::CRLF.static::CRLF;
                $body .= '--'.$this->boundary[1].'--'.static::CRLF;
                break;
            default:
                $body .= $this->encodeString($this->Body, $this->Encoding);
                break;
        }
        return $body;
    }

    protected function getBoundary($boundary, $charSet, $contentType, $encoding) {
        if (!$charSet) $charSet = $this->CharSet;
        if (!$contentType) $contentType = $this->ContentType;
        if (!$encoding) $encoding = $this->Encoding;
        return '--'.$boundary.static::CRLF.'Content-Type: '.$contentType.'; charset='.$charSet.static::CRLF.$this->headerLine('Content-Transfer-Encoding', $encoding).static::CRLF;
    }

    public function encodeString($str, $encoding = self::ENCODING_BASE64) {
        switch (strtolower($encoding)) {
            case static::ENCODING_BASE64: return chunk_split(base64_encode($str), static::STD_LINE_LENGTH, static::CRLF);
            case static::ENCODING_7BIT: case static::ENCODING_8BIT: return static::normalizeBreaks($str).static::CRLF;
            case static::ENCODING_QUOTED_PRINTABLE: return $this->fixEOL(quoted_printable_encode($str)).static::CRLF;
            default: $this->setError('Unknown encoding: '.$encoding); return $str;
        }
    }

    public function encodeHeader($str, $position = 'text') {
        if (!preg_match('/[\x80-\xFF]/', $str)) return $str;
        $encoded = base64_encode($str);
        $maxlen = 75 - 7 - strlen($this->CharSet);
        $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
        return trim(str_replace("\n", static::CRLF, preg_replace('/^(.*)$/m', ' =?'.$this->CharSet.'?B?\1?=', $encoded)));
    }

    public function addrFormat($addr) {
        if (empty($addr[1])) return $this->secureHeader($addr[0]);
        return $this->encodeHeader($this->secureHeader($addr[1]), 'phrase').' <'.$this->secureHeader($addr[0]).'>';
    }

    protected function addrAppend($type, $addr) {
        $addresses = []; foreach ($addr as $a) $addresses[] = $this->addrFormat($a);
        return $this->headerLine($type, implode(', ', $addresses));
    }

    public function headerLine($name, $value) { return $name.': '.$value.static::CRLF; }
    public function textLine($value) { return $value.static::CRLF; }
    protected function secureHeader($str) { return trim(str_replace(["\r","\n"], '', $str)); }
    protected function fixEOL($str) { return str_replace("\n", static::CRLF, str_replace(["\r\n","\r"], "\n", $str)); }
    public static function normalizeBreaks($text, $breaktype = null) { if (!$breaktype) $breaktype = static::CRLF; return preg_replace('/\r\n|\r|\n/m', $breaktype, $text); }
    public static function rfcDate() { date_default_timezone_set(@date_default_timezone_get()); return date('D, j M Y H:i:s O'); }
    protected function serverHostname() { if (!empty($this->Hostname)) return $this->Hostname; if (isset($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME']; return 'localhost.localdomain'; }
    public static function isShellSafe($string) { if (escapeshellcmd($string) !== $string) return false; for ($i=0;$i<strlen($string);$i++) { $c=$string[$i]; if (!ctype_alnum($c) && strpos('@_-.', $c)===false) return false; } return true; }
    public static function isPermittedPath($path) { return !preg_match('#^[a-z][a-z\d+\-.]*://#i', $path); }
    public static function generateId() { return hash('sha256', mt_rand()).substr(str_replace(['+','/','='],'',base64_encode(random_bytes(16))),0,8); }
    public static function validateAddressEx($address) { return (bool)filter_var($address, FILTER_VALIDATE_EMAIL); }
    protected function setError($msg) { ++$this->error_count; $this->ErrorInfo = $msg; }
    public function getLastMessageID() { return $this->lastMessageID; }
    public function isError() { return $this->error_count > 0; }
    public function alternativeExists() { return !empty($this->AltBody); }
    public function attachmentExists() { foreach ($this->attachment as $a) if ($a[6] === 'attachment') return true; return false; }
    public function inlineImageExists() { foreach ($this->attachment as $a) if ($a[6] === 'inline') return true; return false; }
    public function clearAddresses() { foreach ($this->to as $t) unset($this->all_recipients[strtolower($t[0])]); $this->to = []; }
    public function clearCCs() { foreach ($this->cc as $c) unset($this->all_recipients[strtolower($c[0])]); $this->cc = []; }
    public function clearBCCs() { foreach ($this->bcc as $b) unset($this->all_recipients[strtolower($b[0])]); $this->bcc = []; }
    public function clearReplyTos() { $this->ReplyTo = []; }
    public function clearAllRecipients() { $this->to=$this->cc=$this->bcc=[]; $this->all_recipients=[]; }
    public function clearAttachments() { $this->attachment = []; }
    public function clearCustomHeaders() { $this->CustomHeader = []; }
    public function addCustomHeader($name, $value = null) { if ($value === null && strpos($name, ':') !== false) [$name, $value] = explode(':', $name, 2); $this->CustomHeader[] = [trim($name), trim($value ?? '')]; return true; }
    public function getMIMEBody() { return $this->MIMEBody; }
    public function getMIMEHeader() { return $this->MIMEHeader; }
    // smtpConnect is handled by protected smtpConnect inside postSend
    public function getSMTPInstance() { if (!is_object($this->smtp)) $this->smtp = new SMTP(); return $this->smtp; }
    public function setSMTPInstance(SMTP $smtp) { $this->smtp = $smtp; return $this; }
    public function has8bitChars($text) { return (bool)preg_match('/[\x80-\xFF]/', $text); }
    public function punyencodeAddress($address) { return $address; }
    public static function isValidHost($host) { return (bool)filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME); }
}
