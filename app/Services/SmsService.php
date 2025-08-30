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

        public function sendSaleInvoiceSms($transaction, $business_id)
    {
        try {
            // Load the transaction with contact relationship if not already loaded
            if (!$transaction->relationLoaded('contact')) {
                $transaction->load('contact');
            }

            // Get customer's current balance (total due amount)
            $customer_balance = $transaction->contact->balance ?? 0;

            // Get the latest payment details for this transaction
            $latest_payment = $transaction->payment_lines()
                ->where('is_return', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            $payment_amount = $latest_payment ? $latest_payment->amount : $transaction->total_paid;
            $payment_method = $latest_payment ? ucfirst($latest_payment->method) : 'Cash';

            // Format the SMS message according to the new format
            // Sample: Received: ৳[Amount] via [Cash] | Current Due: ৳[Total Due] – WALL TOUCH, Hotline: 01712968571
            $message = "Received: ৳" . number_format($payment_amount, 2) .
                " via " . $payment_method .
                " | Current Due: ৳" . number_format($customer_balance, 2) .
                " – WALL TOUCH, Hotline: 01712968571";

            return $this->sendSms($transaction->contact->mobile, $message, $business_id);

        } catch (\Exception $e) {
            // Log error but don't block the transaction
            \Log::error('Sale invoice SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Core method to send SMS using NotificationUtil
     */
    protected function sendSms($mobile, $message, $business_id = null)
    {
        try {
            // Get business details for SMS settings
            $business = \App\Business::find($business_id);
            
            if (!$business) {
                return ['success' => false, 'message' => 'Business not found'];
            }

            // Prepare SMS data for sending
            $sms_data = [
                'sms_settings' => $business->sms_settings,
                'mobile_number' => $mobile,
                'sms_body' => $message
            ];

            return $this->notificationUtil->sendSms($sms_data);
        } catch (\Exception $e) {
            \Log::error('SMS Service Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send welcome SMS to new customer
     */
    public function sendWelcomeSms($customer_name, $mobile, $business_id = null)
    {
        $message = "Welcome {$customer_name}! You are Added To Our Customer List – WALL TOUCH, Hotline: 01712968571";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send welcome SMS to new supplier
     */
    public function sendSupplierWelcomeSms($supplier_name, $mobile, $business_id = null)
    {
        $message = "Dear {$supplier_name}, You are Added To Our Vendor List – WALL TOUCH, Hotline: 01712968571";
        return $this->sendSms($mobile, $message, $business_id);
    }

    /**
     * Send new sale SMS notification
     */
    public function sendNewSaleSms($transaction, $business_id)
    {
        try {
            // Load the transaction with contact relationship if not already loaded
            if (!$transaction->relationLoaded('contact')) {
                $transaction->load('contact');
            }

            // Calculate previous due amount (balance before this transaction)
            $current_balance = $transaction->contact->balance ?? 0;
            $transaction_amount = $transaction->final_total ?? 0;
            $prev_due = $current_balance - $transaction_amount;

            // Format the SMS message
            $message = "Invoice#{$transaction->invoice_no} | Bill: ৳" . number_format($transaction_amount, 2) .
                " | Prev Due: ৳" . number_format($prev_due, 2) .
                " | Outstanding: ৳" . number_format($current_balance, 2) .
                " – WALL TOUCH, Hotline: 01712968571";

            return $this->sendSms($transaction->contact->mobile, $message, $business_id);

        } catch (\Exception $e) {
            \Log::error('New sale SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send sales return SMS notification
     */
    public function sendSalesReturnSms($transaction, $business_id)
    {
        try {
            // Load the transaction with contact relationship if not already loaded
            if (!$transaction->relationLoaded('contact')) {
                $transaction->load('contact');
            }

            // Calculate previous due amount (balance before this return)
            $current_balance = $transaction->contact->balance ?? 0;
            $return_amount = abs($transaction->final_total ?? 0);
            $prev_due = $current_balance + $return_amount;

            // Format the SMS message
            $message = "Return#{$transaction->invoice_no} | Returned: ৳" . number_format($return_amount, 2) .
                " | Prev Due: ৳" . number_format($prev_due, 2) .
                " | Outstanding: ৳" . number_format($current_balance, 2) .
                " – WALL TOUCH, Hotline: 01712968571";

            return $this->sendSms($transaction->contact->mobile, $message, $business_id);

        } catch (\Exception $e) {
            \Log::error('Sales return SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send supplier payment SMS notification
     */
    public function sendSupplierPaymentSms($contact, $payment, $business_id)
    {
        try {
            // Get current balance (total due amount after payment)
            $current_balance = $contact->balance ?? 0;
            
            // Payment amount
            $payment_amount = $payment->amount ?? 0;
            $payment_method = ucfirst($payment->method ?? 'Cash');
            $cheque_number = $payment->cheque_number ?? 'N/A';

            // Format the SMS message
            $message = "Paid: ৳" . number_format($payment_amount, 2) .
                " via " . $payment_method .
                " | Cheque: " . $cheque_number .
                " | Current Due: ৳" . number_format($current_balance, 2) .
                " – WALL TOUCH, Hotline: 01712968571";

            return $this->sendSms($contact->mobile, $message, $business_id);

        } catch (\Exception $e) {
            \Log::error('Supplier payment SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send shipping update SMS notification
     */
    public function sendShippingSms($transaction, $business_id)
    {
        try {
            // Load the transaction with contact relationship if not already loaded
            if (!$transaction->relationLoaded('contact')) {
                $transaction->load('contact');
            }

            // Prepare shipping information for SMS
            $shipping_info = [];
            
            if (!empty($transaction->shipping_details)) {
                $shipping_info[] = $transaction->shipping_details;
            }
            
            if (!empty($transaction->shipping_address)) {
                $shipping_info[] = $transaction->shipping_address;
            }
            
            if (!empty($transaction->shipping_status)) {
                $shipping_info[] = "Status: " . ucfirst($transaction->shipping_status);
            }
            
            if (!empty($transaction->delivered_to)) {
                $shipping_info[] = "Delivered to: " . $transaction->delivered_to;
            }

            // Add custom shipping fields if they exist
            for ($i = 1; $i <= 5; $i++) {
                $field = "shipping_custom_field_{$i}";
                if (!empty($transaction->$field)) {
                    $shipping_info[] = $transaction->$field;
                }
            }

            $shipping_details = !empty($shipping_info) ? implode(' | ', $shipping_info) : 'Updated';

            // Format the SMS message as requested
            $message = "Your Product has been Sent. Shipping Details: {$shipping_details} | – WALL TOUCH, Hotline: 01712968571";

            return $this->sendSms($transaction->contact->mobile, $message, $business_id);

        } catch (\Exception $e) {
            \Log::error('Shipping SMS failed: ' . $e->getMessage());
            return false;
        }
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
