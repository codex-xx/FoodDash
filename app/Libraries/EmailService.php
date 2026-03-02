<?php

namespace App\Libraries;

require_once ROOTPATH . 'PHPMailer/PHPMailer.php';
require_once ROOTPATH . 'PHPMailer/SMTP.php';
require_once ROOTPATH . 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService - PHPMailer wrapper for FoodDash
 * 
 * This library provides email sending functionality using PHPMailer
 * with Gmail SMTP configuration.
 */
class EmailService
{
    protected $mailer;
    protected $fromEmail = 'vesterlaurel@gmail.com';
    protected $fromName = 'FoodDash';
    protected $smtpHost = 'smtp.gmail.com';
    protected $smtpPort = 587;
    protected $smtpUsername = 'vesterlaurel@gmail.com';
    protected $smtpPassword = 'ksjsjdufyckomaed';

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with SMTP settings
     */
    protected function configure()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $this->smtpHost;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $this->smtpUsername;
            $this->mailer->Password   = $this->smtpPassword;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = $this->smtpPort;

            // Set default sender
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Enable HTML
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';

        } catch (Exception $e) {
            log_message('error', 'PHPMailer configuration error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a password reset email with a 6-digit code
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $resetCode 6-digit reset code
     * @return bool
     */
    public function sendPasswordResetCode(string $toEmail, string $toName, string $resetCode): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = 'FoodDash - Password Reset Code';
            
            $this->mailer->Body = $this->getResetCodeEmailTemplate($toName, $resetCode);
            $this->mailer->AltBody = "Hello {$toName},\n\nYour password reset code is: {$resetCode}\n\nThis code will expire in 15 minutes.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Password reset email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send reset email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a password reset email with a link
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $resetLink Reset link URL
     * @return bool
     */
    public function sendPasswordResetLink(string $toEmail, string $toName, string $resetLink): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = 'FoodDash - Password Reset Request';
            
            $this->mailer->Body = $this->getResetLinkEmailTemplate($toName, $resetLink);
            $this->mailer->AltBody = "Hello {$toName},\n\nWe received a request to reset your password. Click the link below to reset it:\n\n{$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Password reset link email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send reset link email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a generic email
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain text body (optional)
     * @return bool
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody ?: strip_tags($htmlBody);

            $this->mailer->send();
            log_message('info', 'Email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get HTML template for reset code email
     */
    protected function getResetCodeEmailTemplate(string $name, string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">🍕 FoodDash</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Password Reset Request</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>
        
        <p>We received a request to reset your password. Use the code below to complete the process:</p>
        
        <div style="background: #FF6B35; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 25px 0;">
            <span style="font-size: 36px; font-weight: bold; letter-spacing: 8px;">{$code}</span>
        </div>
        
        <p style="color: #666; font-size: 14px;">
            ⏰ This code will expire in <strong>15 minutes</strong>.
        </p>
        
        <p style="color: #666; font-size: 14px;">
            If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
        </p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
            This is an automated message from FoodDash. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for reset link email
     */
    protected function getResetLinkEmailTemplate(string $name, string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">🍕 FoodDash</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Password Reset Request</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>
        
        <p>We received a request to reset your password. Click the button below to create a new password:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$link}" style="background: #FF6B35; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset Password</a>
        </div>
        
        <p style="color: #666; font-size: 14px;">
            ⏰ This link will expire in <strong>1 hour</strong>.
        </p>
        
        <p style="color: #666; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{$link}" style="color: #FF6B35; word-break: break-all;">{$link}</a>
        </p>
        
        <p style="color: #666; font-size: 14px;">
            If you didn't request a password reset, you can safely ignore this email.
        </p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
            This is an automated message from FoodDash. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get the last error message
     */
    public function getError(): string
    {
        return $this->mailer->ErrorInfo;
    }
}
