<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'benzmenguito123@gmail.com';
    public string $fromName   = 'FoodDash';
    public string $recipients = '';

    public string $protocol = 'smtp';
    public string $SMTPHost = 'smtp.gmail.com';
    public int    $SMTPPort = 587;
    public string $SMTPUser = 'benzmenguito123@gmail.com';
    public string $SMTPPass = 'jdlf djeh pggj aavu';
    public string $SMTPCrypto = 'tls';
    public bool   $SMTPAutoTLS = true;

    public string $mailType = 'html';
    public string $charset  = 'UTF-8';
    public string $newline  = "\r\n";
}