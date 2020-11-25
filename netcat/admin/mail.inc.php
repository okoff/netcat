<?php

/* $Id: mail.inc.php 8329 2012-11-02 11:31:02Z vadim $ */

/**
 * Quoted-printable header encoder (splited into 76-char chunks)
 *
 * @param string $input
 * @param string $charset
 * @return string
 */
function nc_quoted_printable_encode_header($input, $charset = MAIN_EMAIL_ENCODING) {
    $str = preg_replace("/([^\x09\x21-\x3C\x3E-\x7E])/e",
                    '"=".strtoupper(dechex(ord("$1")))',
                    rtrim($input));

    // add encoding to the beginning of each line
    $encoding = "=?$charset?Q?";
    $content_length = 72 - strlen($encoding);

    nc_preg_match_all("/.{1,$content_length}([^=]{0,2})?/", $str, $regs);
    $str = $encoding.join("?=\r\n\t$encoding", $regs[0])."?=";

    return $str;
}

/**
 * base64 header encoder
 *
 * @param string $input
 * @param string $charset
 * @return string
 */
function nc_base64_encode_header($input, $charset = MAIN_EMAIL_ENCODING) {
    $str = preg_replace("/([^\x09\x21-\x3C\x3E-\x7E])/e",
                    '"=".strtoupper(dechex(ord("$1")))',
                    rtrim($input));

    // add encoding to the beginning of each line
    $str = "=?$charset?B?".base64_encode($input)."?=";
    return $str;
}

/**
 * Quoted-printable string encoder
 *
 * @param string $input
 * @return string
 */
function nc_quoted_printable_encode($input) {
    $tohex = '"=".strtoupper(dechex(ord("$1")))';

    $str = preg_replace('/([^\x09\x20\x0D\x0A\x21-\x3C\x3E-\x7E])/e', $tohex, $input);
    // encode x20, x09 at the end of lines
    $str = preg_replace("/([\x20\x09])(\r?\n)/e", $tohex.'$2', $str);
    $str = str_replace("\r", "", $str);

    // split into chunks
    // Из-за разбиения строки по RFC (=CRLF) возникают "лишние" переносы строк на некоторых почтовых серверах

    $lines = explode("\n", $str);
    foreach ($lines as $num => $line) {
        if (strlen($line) > 76) {
            nc_preg_match_all('/.{1,73}([^=]{0,2})?/', $line, $regs);
            $lines[$num] = join("=\n", $regs[0]);
        }
    }
    $str = join("\n", $lines);

    return $str;
}

// ----------------------



class CMIMEMail {

    var $to;
    var $reply;
    var $boundary = "--=_NetxPart_234_Net_Cat_sMdAdsf0sGdAsfDAfN";
    var $filename_real;
    var $body_plain;
    var $body_html;
    var $atcmnt;
    var $atcmnt_type;
    var $original_name;
    var $from_name;
    var $charset;
    var $isHtml = false;
    var $isAttach = false;

    function CMIMEMail($priority=3) {
        $this->priority = $priority;
        $this->charset = "";
        if (!defined("MAIN_EMAIL_ENCODING")) {
            define("MAIN_EMAIL_ENCODING", "windows-1251");
        }
    }

    function mailbody($plain, $html= "") {
        $this->body_plain = $plain;
        $this->body_html = $html;
        $this->isHtml = $html ? true : false;
        return;
    }

    function attach($name, $original_name, $content_type, $data) {
        $this->atcmnt[$name] = $data;
        $this->atcmnt_type[$name] = $content_type;
        $this->original_name[$name] = $original_name;
        return;
    }

    function attachFile($fname, $original_name, $content_type) {
        $name = $fname;
        $f = fopen($name, "r");
        $size = filesize($name);
        $contents = fread($f, $size);

        $this->attach($name, $original_name, $content_type, $contents);
        fclose($f);
        $this->isAttach = true;
        return;
    }

    function clear() {
        unset($atcmnt);
        unset($atcmnt_type);
        $this->isAttach = false;
        return;
    }

    function setCharset($new_charset) {
        $this->charset = $new_charset;
        return;
    }

    function makeheader() {
        $out = "From: ".nc_base64_encode_header($this->from_name, $this->charset)." <".$this->from.">\n";
        $out .= "Reply-To: <".$this->reply.">\n";
        $out .= "Return-Path: <".$this->reply.">\n";
        $out .= "MIME-Version: 1.0\n";

        switch (true) { //тип письма
            case ( $this->isHtml && !$this->isAttach ): //html
                $out .= "Content-Type: multipart/alternative;\n boundary=\"".$this->boundary."\"\n";
                break;
            case (!$this->isHtml && !$this->isAttach ): // text
                $out.= "Content-Type: text/plain; charset=".$this->charset."\nContent-Transfer-Encoding: quoted-printable\n";
                break;
            case ( $this->isAttach ): //html or text + file
                $out .= "Content-Type: multipart/mixed; boundary=\"".$this->boundary."\"\n";
                break;
        }

        //$out .= "X-Priority: ".$this->priority. "\n\n";
        $out .= "X-Priority: ".$this->priority."\n";

        return $out;
    }

    function makebody() {
        $out = "";

        switch (true) {
            case ( $this->isHtml && !$this->isAttach ): //html
                $out .= "--".$this->boundary."\n";
                $out .= "Content-type: text/plain;charset=\"".$this->charset."\"\n";
                $out .= "Content-Transfer-Encoding: quoted-printable\n\n".nc_quoted_printable_encode($this->body_plain)."\r\n\r\n";
                $out .= "--".$this->boundary."\n";
                $out .= "Content-type: text/html;charset=\"".$this->charset."\"\n";
                $out .= "Content-Transfer-Encoding: quoted-printable\n\n".nc_quoted_printable_encode($this->body_html)."\r\n\r\n";
                $out .= "--".$this->boundary."--\n";
                break;
            case (!$this->isHtml && !$this->isAttach ): // text
                $out .= nc_quoted_printable_encode($this->body_plain)."\n";
                break;
            case ( $this->isAttach ):  // +file
                $out .= "--".$this->boundary."\n";
                if ($this->isHtml) {
                    $out .= "Content-type: text/html;charset=\"".$this->charset."\"\n";
                    $out .= "Content-Transfer-Encoding: quoted-printable\n\n".nc_quoted_printable_encode($this->body_html)."\r\n\r\n";
                } else {
                    $out .= "Content-type: text/plain;charset=\"".$this->charset."\"\n";
                    $out .= "Content-Transfer-Encoding: quoted-printable\n\n".nc_quoted_printable_encode($this->body_plain)."\r\n\r\n";
                }

                @reset($this->atcmnt_type);
                while (list($name, $content_type) = @each($this->atcmnt_type)) {
                    $out .= "--".$this->boundary."\n";
                    $out .= "Content-Type: ".$content_type."\n";
                    $out .= "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"".$this->original_name[$name]."\"\r\n\r\n".
                            chunk_split(base64_encode($this->atcmnt[$name]))."\r\n";
                }
                $out .= "--".$this->boundary."--\n";
                break;
        }

        return $out;
    }

    function send($to, $from, $reply, $subject, $from_name) {
        $this->to = $to;
        $this->from = $from;
        $this->subject = $subject;
        $this->reply = $reply;
        $this->from_name = $from_name;
        if (!$this->charset) $this->charset = MAIN_EMAIL_ENCODING;

        return mail($this->to, nc_base64_encode_header($this->subject, $this->charset), $this->makebody(), $this->makeheader());
    }

}

class CMIMEMailExt extends CMIMEMail {

    var $embed_type, $cid;

    function CMimeMailExt($priority=3) {
        parent::CMimeMail($priority);
        $this->embed = array();
        $this->cid = array();
    }

    function makebody() {
        if (empty($this->cid)) return parent::makebody();

        $boundary2 = "----=_NextPart_001_0009_01BF95E9.CDFD2062";
        $boundary3 = "----=_NextPart_001_0009_01BF95E9.CDFD2064";

        $out = "";

        if ($this->body_html != "") {

            $out.= "--".$this->boundary."\nContent-Type: text/html; charset=".$this->charset."\nContent-Transfer-Encoding: quoted-printable\n\n".
                    nc_quoted_printable_encode($this->body_html).
                    "\n\n--$this->boundary--\n";

            if ($this->body_plain) {
                $out.= "--".$this->boundary."\nContent-Type: multipart/alternative;\n boundary=$boundary2\n\n";
                $out.= "--$boundary2\nContent-Type: text/plain;charset=\"".$this->charset."\"\nContent-Transfer-Encoding: quoted-printable\n\n".$this->body_plain."\n\n";
            }

            $out.= "--".$this->boundary."\nContent-Type: multipart/related;\n boundary=$boundary3\n\n";

            @reset($this->embed_type);
            while (list($name, $content_type) = @each($this->embed_type)) {
                $out.= "\n--".$boundary3."\nContent-Type: $content_type;\n name=\"".$this->original_name[$name]."\"\nContent-Transfer-Encoding: base64\nContent-ID: <".$this->cid[$name].">\n\n".
                        chunk_split(base64_encode($this->atcmnt[$name]))."\n";
            }
            $out.="\n\n--$boundary3--\n";

            if ($this->body_plain) $out.="\n\n--$boundary2--\n";
        }
        else {
            $out.= "--".$this->boundary."\nContent-type: text/plain;charset=\"".$this->charset."\"\nContent-Transfer-Encoding: quoted-printable\n\n".
                    nc_quoted_printable_encode($this->body_plain).
                    "\n";
        }


        @reset($this->atcmnt_type);
        while (list($name, $content_type) = @each($this->atcmnt_type)) {

            $out.= "\n--".$this->boundary."\nContent-Type: $content_type\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"".$this->original_name[$name]."\"\n\n".
                    chunk_split(base64_encode($this->atcmnt[$name]))."\n";
        }
        $out.= "--".$this->boundary."--\n";
        return $out;
    }

    function attachEmbed($name, $original_name, $content_type, $data) {
        $this->atcmnt[$name] = $data;
        $this->embed_type[$name] = $content_type;
        $this->original_name[$name] = $original_name;
        $this->cid[$name] = "NC".md5(uniqid(rand(), true));
        return $this->cid[$name];
    }

    function attachFileEmbed($fname, $original_name, $content_type) {
        $name = $fname;
        $f = fopen($name, "r");
        $size = filesize($name);
        $contents = fread($f, $size);
        $this->isAttach = true;
        return $this->attachEmbed($name, $original_name, $content_type, $contents);
        fclose($f);
    }

}

/**
 * Положить письмо в очередь
 *
 * @param string $recipient
 * @param string $from
 * @param string $subject
 * @param string $message
 * @param string HTML-сообщение
 *
 * Чтобы отправить сообщение в формате HTML, нужно указать параметр html_message.
 * При этом параметр message должен содержать сообщение в plain text или может быть пустым
 *
 * Чтобы отправить plain text, параметр html_message нужно оставить пустым.
 */
function nc_mail2queue($recipient, $from, $subject, $message, $html_message="") {

    require_once("Mail/Queue.php");

    $db_options = array('type' => 'ezsql', 'mail_table' => 'Mail_Queue');
    $mail_options = array('driver' => 'mail');

    $mail_queue = new Mail_Queue($db_options, $mail_options);

    $hdrs = array('From' => $from, // email only (no name!)
            'Subject' => nc_base64_encode_header($subject));

    $mime = new Mail_mime();
    if ($message) $mime->setTXTBody($message);
    if ($html_message) $mime->setHTMLBody($html_message);
    $body = $mime->get(array('text_encoding' => '8bit', 'html_charset' => MAIN_EMAIL_ENCODING,
                    'text_charset' => MAIN_EMAIL_ENCODING, 'head_charset' => MAIN_EMAIL_ENCODING));
    $hdrs = $mime->headers($hdrs);

    $mail_queue->put($from, $recipient, $hdrs, $body);
}