<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/15
 * Time: 2:52 PM
 */

namespace App\Services;

use Log;

class InterSendMailService
{
    private static $instance = null;
    /**
     * 收件人
     *
     * @var
     */
    private $to;

    /**
     * 邮件主题
     *
     * @var
     */
    private $subject;

    /**
     * 邮件正文部分
     *
     * @var
     */
    private $msg;

    /**
     * 特殊头部分说明
     *
     * @var
     */
    private $headers;

    /**
     * 邮件正文是否包含html标签部分
     *
     * @var
     */
    private $html;

    /**
     * InterSendMailService constructor.
     *  发送邮件
     *
     * @param $to
     * @param $subject
     * @param $msg
     * @param null $header
     */
    private function __construct($to, $subject, $msg, $html = false)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->msg = $msg;
        $this->html = $html;
        if ($html) {
            $this->getHeaders();
        }
    }

    /**
     * 当发送正文部分包含html标签时，调用此部分
     *
     * @return void
     */
    private function getHeaders()
    {
        if ($this->html) {
            $this->headers = "X-Mailer:PHP";
            $this->headers .= "MIME-Version: 1.0";
            $this->headers .= "PHP-Version:phpversion()\r\n";
            $this->headers .= "Content-type:text/html;charset=utf-8\r\n";
            $this->headers .= "From:dau_monitor@vip.sina.com\r\n";
            $this->headers .= "Reply-To:dau_monitor@vip.sina.com\r\n";
        }
    }

    public function sendfile()
    {
        Log::info('send email, the receive email: '.$this->to.' ; the subject: '.$this->subject .' ; the msg: '.$this->msg . ' ; the html: '.$this->html);
        if ($this->html) {
            $res = mail ($this->to, $this->subject, $this->msg, $this->headers);
        } else {
            $res = mail($this->to, $this->subject, $this->msg);
        }

        Log::info('The result of sending email: '.($res ? 'successful' : 'fail' ));
    }

    public static function getEmailInstance($to, $subject, $msg, $html)
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self($to, $subject, $msg, $html);

        return self::$instance;
    }
}