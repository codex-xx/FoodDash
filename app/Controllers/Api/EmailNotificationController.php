<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class EmailNotificationController extends ResourceController
{
    protected $format = 'json';

    /**
     * Send email notification based on event type
     * POST /api/send-notification-email
     */
    public function sendNotification()
    {
        try {
            // Get JSON input
            $json = $this->request->getJSON();
            
            if (!$json) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Extract parameters
            $event = $json->event ?? '';
            $email = $json->email ?? '';
            $name = $json->name ?? '';
            $role = $json->role ?? '';

            // Validate required fields
            if (empty($event) || empty($email)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Event and email are required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email address'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Load email library
            $emailService = \Config\Services::email();

            // Prepare email content based on event type
            $subject = '';
            $message = '';

            switch ($event) {
                case 'driver_application_received':
                    $subject = 'FoodDash - Driver Application Received';
                    $message = $this->getDriverApplicationReceivedTemplate($name);
                    break;

                case 'driver_application_approved':
                    $subject = 'FoodDash - Driver Application Approved!';
                    $message = $this->getDriverApplicationApprovedTemplate($name);
                    break;

                case 'customer_registration_success':
                    $subject = 'Welcome to FoodDash!';
                    $message = $this->getCustomerRegistrationTemplate($name);
                    break;

                default:
                    return $this->respond([
                        'success' => false,
                        'message' => 'Unknown event type: ' . $event
                    ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Configure email
            $emailService->setFrom('noreply@fooddash.com', 'FoodDash');
            $emailService->setTo($email);
            $emailService->setSubject($subject);
            $emailService->setMessage($message);

            // Send email
            if ($emailService->send()) {
                log_message('info', "Email sent successfully to {$email} for event {$event}");
                
                return $this->respond([
                    'success' => true,
                    'message' => 'Email notification sent successfully',
                    'data' => [
                        'event' => $event,
                        'recipient' => $email
                    ]
                ], ResponseInterface::HTTP_OK);
            } else {
                // Log detailed error
                $error = $emailService->printDebugger(['headers']);
                log_message('error', "Failed to send email to {$email}: " . $error);
                
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to send email notification',
                    'error' => $error
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            log_message('error', 'Email notification error: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred while sending email',
                'error' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Email template for driver application received
     */
    private function getDriverApplicationReceivedTemplate($name)
    {
        $displayName = !empty($name) ? $name : 'Driver';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #FF6B35; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>FoodDash Driver Application</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$displayName},</h2>
                    <p>Thank you for applying to become a FoodDash driver!</p>
                    <p>We have successfully received your application and it is currently <strong>pending admin approval</strong>.</p>
                    <p><strong>What happens next?</strong></p>
                    <ul>
                        <li>Our admin team will review your application</li>
                        <li>You will receive another email once your application is approved</li>
                        <li>After approval, you can log in to the FoodDash app and start accepting deliveries</li>
                    </ul>
                    <p>This process typically takes 1-2 business days.</p>
                    <p>Thank you for your patience!</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 FoodDash. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Email template for driver application approved
     */
    private function getDriverApplicationApprovedTemplate($name)
    {
        $displayName = !empty($name) ? $name : 'Driver';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
                .highlight { background-color: #4CAF50; color: white; padding: 15px; text-align: center; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Congratulations!</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$displayName},</h2>
                    <div class='highlight'>
                        <h3>Your FoodDash driver application has been APPROVED!</h3>
                    </div>
                    <p>We're excited to welcome you to the FoodDash driver team!</p>
                    <p><strong>You can now:</strong></p>
                    <ul>
                        <li>Log in to the FoodDash driver app</li>
                        <li>Start accepting delivery requests</li>
                        <li>Begin earning money with FoodDash</li>
                    </ul>
                    <p><strong>Important reminders:</strong></p>
                    <ul>
                        <li>Always ensure your vehicle is roadworthy and clean</li>
                        <li>Follow delivery policies and safety rules</li>
                        <li>Provide excellent customer service</li>
                        <li>Keep your contact information up to date</li>
                    </ul>
                    <p>Welcome aboard, and happy delivering!</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 FoodDash. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Email template for customer registration
     */
    private function getCustomerRegistrationTemplate($name)
    {
        $displayName = !empty($name) ? $name : 'Valued Customer';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to FoodDash!</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$displayName},</h2>
                    <p>Thank you for registering with FoodDash! Your account has been successfully created.</p>
                    <p><strong>You can now:</strong></p>
                    <ul>
                        <li>Browse delicious food from local restaurants</li>
                        <li>Place orders for delivery</li>
                        <li>Track your deliveries in real-time</li>
                        <li>Save your favorite restaurants and meals</li>
                    </ul>
                    <p>We're excited to have you as part of the FoodDash community!</p>
                    <p>If you have any questions or need assistance, feel free to contact our support team.</p>
                    <p>Happy ordering!</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 FoodDash. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Test endpoint to verify email configuration
     * GET /api/test-email
     */
    public function testEmail()
    {
        try {
            $email = $this->request->getGet('email');
            
            if (empty($email)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Please provide email parameter'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $emailService = \Config\Services::email();
            $emailService->setFrom('noreply@fooddash.com', 'FoodDash');
            $emailService->setTo($email);
            $emailService->setSubject('FoodDash - Email Configuration Test');
            $emailService->setMessage('This is a test email from FoodDash. If you received this, your email configuration is working correctly!');

            if ($emailService->send()) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $email
                ], ResponseInterface::HTTP_OK);
            } else {
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to send test email',
                    'error' => $emailService->printDebugger(['headers'])
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
