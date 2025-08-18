<?php

namespace App\Traits;

use App\Services\SmsService;

/**
 * Trait for easy SMS functionality in controllers
 * This trait provides convenient methods to send SMS messages
 */
trait SendsSms
{
    /**
     * Get the SMS service instance
     */
    protected function smsService()
    {
        return app(SmsService::class);
    }

    /**
     * Send a simple SMS
     */
    protected function sendSms($mobile, $message)
    {
        return $this->smsService()->sendSms($mobile, $message);
    }

    /**
     * Send welcome SMS to new customer
     */
    protected function sendWelcomeSms($customer_name, $mobile)
    {
        return $this->smsService()->sendWelcomeSms($customer_name, $mobile);
    }

    /**
     * Send welcome SMS to new supplier
     */
    protected function sendSupplierWelcomeSms($supplier_name, $mobile)
    {
        $template = "Dear {supplier_name}, আপনি সফলভাবে আমাদের Vendor List-এ যুক্ত হয়েছেন। সহযোগিতার জন্য আন্তরিক ধন্যবাদ। – WALL TOUCH, Hotline: 01712968571";
        $variables = ['supplier_name' => $supplier_name];
        return $this->sendCustomSms($mobile, $template, $variables);
    }

    /**
     * Send payment confirmation SMS
     */
    protected function sendPaymentConfirmationSms($mobile, $amount, $method, $cheque_number = null, $total_due = 0)
    {
        $cheque_info = '';
        if ($method == 'cheque' && !empty($cheque_number)) {
            $cheque_info = " | Cheque: {$cheque_number}";
        }
        
        $template = "Paid: ৳{amount} via {method}{cheque_info} | Current Due: ৳{total_due} | পেমেন্ট প্রদান করা হয়েছে – WALL TOUCH, Hotline: 01712968571";
        $variables = [
            'amount' => $amount,
            'method' => $method,
            'cheque_info' => $cheque_info,
            'total_due' => $total_due
        ];
        
        return $this->sendCustomSms($mobile, $template, $variables);
    }

    /**
     * Send order confirmation SMS
     */
    protected function sendOrderConfirmationSms($customer_name, $mobile, $order_number, $total_amount)
    {
        return $this->smsService()->sendOrderConfirmationSms($customer_name, $mobile, $order_number, $total_amount);
    }

    /**
     * Send payment reminder SMS
     */
    protected function sendPaymentReminderSms($customer_name, $mobile, $due_amount)
    {
        return $this->smsService()->sendPaymentReminderSms($customer_name, $mobile, $due_amount);
    }

    /**
     * Send OTP SMS
     */
    protected function sendOtpSms($mobile, $otp)
    {
        return $this->smsService()->sendOtpSms($mobile, $otp);
    }

    /**
     * Send custom SMS with template variables
     */
    protected function sendCustomSms($mobile, $template, $variables = [])
    {
        return $this->smsService()->sendCustomSms($mobile, $template, $variables);
    }

    /**
     * Check if SMS is configured for current business
     */
    protected function isSmsConfigured($business_id = null)
    {
        if (is_null($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }
        return $this->smsService()->isSmsConfigured($business_id);
    }
}
