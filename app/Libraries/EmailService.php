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
    protected $fromEmail = 'benzmenguito123@gmail.com';
    protected $fromName = 'FoodDash';
    protected $smtpHost = 'smtp.gmail.com';
    protected $smtpPort = 587;
    protected $smtpUsername = 'benzmenguito123@gmail.com';
    protected $smtpPassword = 'jdlf djeh pggj aavu';

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
     * Send a login OTP email for MFA verification
     */
    public function sendLoginOtp(string $toEmail, string $toName, string $otpCode): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);

            $this->mailer->Subject = 'FoodDash - Login Verification Code';
            $this->mailer->Body = $this->getLoginOtpEmailTemplate($toName, $otpCode);
            $this->mailer->AltBody = "Hello {$toName},\n\nYour FoodDash login verification code is: {$otpCode}\n\nThis code will expire in 5 minutes.\n\nIf this wasn't you, please change your password immediately.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Login OTP email sent to: ' . $toEmail);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to send login OTP email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a registration OTP email for account verification
     */
    public function sendRegisterOtp(string $toEmail, string $toName, string $otpCode): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);

            $this->mailer->Subject = 'FoodDash - Registration Verification Code';
            $this->mailer->Body = $this->getRegisterOtpEmailTemplate($toName, $otpCode);
            $this->mailer->AltBody = "Hello {$toName},\n\nYour FoodDash registration verification code is: {$otpCode}\n\nThis code will expire in 10 minutes.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Registration OTP email sent to: ' . $toEmail);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to send registration OTP email to ' . $toEmail . ': ' . $e->getMessage());
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
            ⏰ This code will expire in <strong>5 minutes</strong>.
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
     * Get HTML template for login OTP email
     */
    protected function getLoginOtpEmailTemplate(string $name, string $code): string
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
        <h1 style="color: white; margin: 0; font-size: 28px;">FoodDash</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Login Verification</p>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>

        <p>Use this verification code to complete your login:</p>

        <div style="background: #FF6B35; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 25px 0;">
            <span style="font-size: 36px; font-weight: bold; letter-spacing: 8px;">{$code}</span>
        </div>

        <p style="color: #666; font-size: 14px;">
            This code will expire in <strong>10 minutes</strong>.
        </p>

        <p style="color: #666; font-size: 14px;">
            If you did not try to log in, please reset your password.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for registration OTP email
     */
    protected function getRegisterOtpEmailTemplate(string $name, string $code): string
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
        <h1 style="color: white; margin: 0; font-size: 28px;">FoodDash</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Account Registration Verification</p>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>

        <p>Use this verification code to complete your account registration:</p>

        <div style="background: #FF6B35; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 25px 0;">
            <span style="font-size: 36px; font-weight: bold; letter-spacing: 8px;">{$code}</span>
        </div>

        <p style="color: #666; font-size: 14px;">
            This code will expire in <strong>10 minutes</strong>.
        </p>

        <p style="color: #666; font-size: 14px;">
            If you did not request this registration, please ignore this email.
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

    /**
     * Send application received confirmation email (for driver or restaurant)
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $partnerType 'driver' or 'restaurant'
     * @return bool
     */
    public function sendApplicationReceived(string $toEmail, string $toName, string $partnerType): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = 'FoodDash - Application Received';
            
            $partnerTypeLabel = ($partnerType === 'driver') ? 'Driver' : 'Restaurant';
            
            $this->mailer->Body = $this->getApplicationReceivedEmailTemplate($toName, $partnerTypeLabel);
            $this->mailer->AltBody = "Hello {$toName},\n\nThank you for applying to become a FoodDash {$partnerTypeLabel}. We have received your application and our team will review it within 2-3 business days.\n\nWe will send you another email once your application has been reviewed.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Application received email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send application received email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send application approved email
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $partnerType 'driver' or 'restaurant'
     * @return bool
     */
    public function sendApplicationApproved(string $toEmail, string $toName, string $partnerType, ?string $defaultPassword = null): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = 'FoodDash - Application Approved!';
            
            $partnerTypeLabel = ($partnerType === 'driver') ? 'Driver' : 'Restaurant';
            
            $this->mailer->Body = $this->getApplicationApprovedEmailTemplate($toName, $partnerTypeLabel, $defaultPassword);
            $this->mailer->AltBody = "Congratulations {$toName}!\n\nYour application to become a FoodDash {$partnerTypeLabel} has been approved!\n\nYou can now log in to your account and start using our platform." .
                ($defaultPassword ? "\n\nDefault login password: {$defaultPassword}" : "") .
                "\n\nIf you have any questions, please contact our support team.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Application approved email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send application approved email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send application rejected email
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $partnerType 'driver' or 'restaurant'
     * @param string|null $reason Rejection reason
     * @return bool
     */
    public function sendApplicationRejected(string $toEmail, string $toName, string $partnerType, ?string $reason = null): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->Subject = 'FoodDash - Application Update';
            
            $partnerTypeLabel = ($partnerType === 'driver') ? 'Driver' : 'Restaurant';
            
            $this->mailer->Body = $this->getApplicationRejectedEmailTemplate($toName, $partnerTypeLabel, $reason);
            $this->mailer->AltBody = "Hello {$toName},\n\nThank you for your interest in becoming a FoodDash {$partnerTypeLabel}.\n\nAfter careful review, we regret to inform you that your application was not approved at this time." . 
                ($reason ? "\n\nReason: " . $reason : "") . "\n\nIf you have any questions, please contact our support team.\n\nBest regards,\nFoodDash Team";

            $this->mailer->send();
            log_message('info', 'Application rejected email sent to: ' . $toEmail);
            return true;

        } catch (Exception $e) {
            log_message('error', 'Failed to send application rejected email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get HTML template for application received email
     */
    protected function getApplicationReceivedEmailTemplate(string $name, string $partnerType): string
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
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Application Received</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>
        
        <p>Thank you for applying to become a FoodDash {$partnerType}!</p>
        
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; color: #856404;">
                ⏳ <strong>Application Status: Under Review</strong><br>
                <span style="font-size: 14px;">We have received your application and our team will review it within 2-3 business days.</span>
            </p>
        </div>
        
        <p>We will send you another email once your application has been reviewed. In the meantime, please make sure your contact information is up to date.</p>
        
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
     * Get HTML template for application approved email
     */
    protected function getApplicationApprovedEmailTemplate(string $name, string $partnerType, ?string $defaultPassword = null): string
    {
        $credentialsSection = '';

        if ($defaultPassword !== null && $defaultPassword !== '') {
            $escapedPassword = esc($defaultPassword);
            $credentialsSection = <<<HTML
        <div style="background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; color: #856404;">
                <strong>Your default login password:</strong><br>
                {$escapedPassword}
            </p>
        </div>

HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">🎉 Congratulations!</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Your Application Has Been Approved</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>
        
        <p>Great news! Your application to become a FoodDash {$partnerType} has been <strong style="color: #28a745;">approved</strong>!</p>
        
        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; color: #155724;">
                ✅ <strong>You can now log in to your account</strong><br>
                <span style="font-size: 14px;">Start using our platform and grow your business with FoodDash!</span>
            </p>
        </div>

        {$credentialsSection}
        
        <p>You can now:</p>
        <ul style="color: #333;">
            <li>Log in to your dashboard</li>
            <li>Complete your profile</li>
            <li>Start accepting orders</li>
        </ul>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="#" style="background: #FF6B35; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Log In Now</a>
        </div>
        
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
     * Get HTML template for application rejected email
     */
    protected function getApplicationRejectedEmailTemplate(string $name, string $partnerType, ?string $reason = null): string
    {
        $reasonSection = $reason ? "
        <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin: 20px 0;'>
            <p style='margin: 0; color: #721c24;'>
                <strong>Reason:</strong><br>
                {$reason}
            </p>
        </div>
        " : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">🍕 FoodDash</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Application Update</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hello, {$name}!</h2>
        
        <p>Thank you for your interest in becoming a FoodDash {$partnerType}.</p>
        
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; color: #721c24; text-align: center;">
                <strong>After careful review, we regret to inform you that your application was not approved at this time.</strong>
            </p>
        </div>
        
        {$reasonSection}
        
        <p>We encourage you to review our requirements and apply again in the future if you become eligible. If you have any questions, please contact our support team.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
            This is an automated message from FoodDash. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;
    }
}
