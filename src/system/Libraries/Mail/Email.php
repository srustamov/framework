<?php namespace TT\Libraries\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use TT\Facades\Language;
use TT\Facades\Config;
use TT\Engine\App;

class Email extends PHPMailer
{
    private $config;



    public function __construct()
    {
        parent::__construct();

        $this->setLanguage(Language::locale());

        $this->config = Config::get('mail');

        $this->isSMTP();

        $this->SMTPAuth = true;

        $this->SMTPSecure = $this->config[ 'smtp_secure' ];

        $this->isHTML($this->config[ 'html' ]);

        $this->Host = $this->config[ 'host' ];

        $this->Username = $this->config[ 'username' ];

        $this->Password = $this->config[ 'password' ];

        $this->Port = $this->config[ 'port' ];

        $this->CharSet = $this->config[ 'charset' ];

        $this->SMTPDebug = $this->config[ 'show_error' ];
    }


    /**
     * @param    string $email
     * @param    string $name
     * @return Email
     */
    public function from($email, $name = null)
    {
        $this->From = $email;

        $this->AddReplyTo($email, $name);

        if (!is_null($name)) {
            $this->FromName = $name;
        }


        return $this;
    }

    /**
     * @param    string $email
     * @param    string $name
     * @return    Email
     */
    public function to($email, $name = null)
    {
        $this->AddAddress($email, $name);
        return $this;
    }

    /**
     * @param    string $subject
     * @return    Email
     */
    public function subject($subject)
    {
        $this->Subject = $subject;
        return $this;
    }

    /**
     * @param    string $message
     * @return    Email
     */
    public function message($message)
    {
        $this->Body = $message;
        return $this;
    }


    /**
     * PhpMailer Destruct
     */
    public function __destruct()
    {
        parent::__destruct();
    }
}
