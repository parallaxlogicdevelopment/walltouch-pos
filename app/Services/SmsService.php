<?php

namespace App\Services;

use App\Business;
use App\Utils\NotificationUtil;

/**
 * Centralized SMS Service
 * Provides a unified interface for sending SMS messages across the application
 */
class SmsService
{
    protected $notificationUtil;

    public function __construct()
    {
        $this->notificationUtil = new NotificationUtil();
    }

    /**
     * Send a simple SMS message
     */
    public function sendSms($mobile, $message, $business_id = null)
    {
        try {
            if (is_null($business_id)) {
                $business_id = request()->session()->get('user.business_id');
            }

            $business = Business::find($business_id);
            
            if (!$business || !$this->isSmsConfigured($business_id)) {
                return [
                    'success' => false,
                    'message' => 'SMS not configured for this business',
                    'data' => null
                ];
            }

            $sms_data = [
                'sms_settings' => $business->sms_settings,
                'mobile_number' => $mobile,
                'sms_body' => $message
            ];

            $this->notificationUtil->sendSms($sms_data);
            
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => ['mobile' => $mobile, 'message' => $message]
            ];

        } catch (\Exception $e) {
            \Log::error('SMS Service Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send welcome SMS to new customer
     */
    public function sendWelcomeSms($customer_name, $mobile, $business_id = null)
    {
        $message = "Welcome {$customer_name}! আপনি এখন আমাদের সিস্টেমে যুক্ত হয়েছেন। আন্তরিক ধন্যবাদ আমাদের সাথে থাকার জন্য। – WALL TOUCH, Hotline: 01712968571";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send order confirmation SMS
     */
    public function sendOrderConfirmationSms($customer_name, $mobile, $order_number, $total_amount, $business_id = null)
    {
        $message = "Dear {$customer_name}, আপনার অর্ডার #{$order_number} নিশ্চিত করা হয়েছে। Total: {$total_amount} টাকা। ধন্যবাদ! - WALL TOUCH, Hotline: 01712968571";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send payment reminder SMS
     */
    public function sendPaymentReminderSms($customer_name, $mobile, $due_amount, $business_id = null)
    {
        $message = "Dear {$customer_name}, আপনার {$due_amount} টাকা বকেয়া রয়েছে। অনুগ্রহ করে শীঘ্রই পরিশোধ করুন। - WALL TOUCH, Hotline: 01712968571";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send OTP SMS
     */
    public function sendOtpSms($mobile, $otp, $business_id = null)
    {
        $message = "Your OTP code is: {$otp}. This code will expire in 5 minutes. Do not share this code with anyone. - WALL TOUCH";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send custom SMS with template variables
     */
    public function sendCustomSms($mobile, $template, $variables = [], $business_id = null)
    {
        $message = $template;
        
        // Replace template variables
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send bulk SMS to multiple recipients
     */
    public function sendBulkSms($mobile_numbers, $message, $business_id = null)
    {
        $results = [];
        
        foreach ($mobile_numbers as $mobile) {
            $results[] = array_merge(
                ['mobile' => $mobile],
                $this->sendSms($mobile, $message, $business_id)
            );
            
            // Small delay to prevent rate limiting
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }

    /**
     * Check if SMS is configured for the business
     */
    public function isSmsConfigured($business_id)
    {
        try {
            $business = Business::find($business_id);
            
            if (!$business || empty($business->sms_settings)) {
                return false;
            }

            $sms_settings = $business->sms_settings;
            $sms_service = $sms_settings['sms_service'] ?? null;

            if (empty($sms_service)) {
                return false;
            }

            // Check if the selected service has required settings
            switch ($sms_service) {
                case 'nexmo':
                    return !empty($sms_settings['nexmo']['key']) && !empty($sms_settings['nexmo']['secret']);
                
                case 'twilio':
                    return !empty($sms_settings['twilio']['sid']) && !empty($sms_settings['twilio']['token']);
                
                case 'custom':
                    return !empty($sms_settings['custom']['url']);
                
                default:
                    return false;
            }
        } catch (\Exception $e) {
            \Log::error('SMS Configuration Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get SMS statistics for a business
     */
    public function getSmsStats($business_id)
    {
        // This can be extended to track SMS usage, costs, etc.
        return [
            'configured' => $this->isSmsConfigured($business_id),
            'total_sent' => 0, // Can be tracked in database
            'failed_count' => 0, // Can be tracked in database
            'last_sent' => null // Can be tracked in database
        ];
    }
}
