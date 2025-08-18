# SMS Integration Test Guide

## Testing the New SMS Functionality

### Test Scenarios

#### 1. Test Customer Creation with SMS
```php
// Go to Contact > Customers > Add
// Fill in the form with:
- Type: Customer  
- Name: রহিম আহমেদ
- Mobile: 01712968571
- Save

Expected SMS: "Welcome রহিম আহমেদ! আপনি এখন আমাদের সিস্টেমে যুক্ত হয়েছেন। আন্তরিক ধন্যবাদ আমাদের সাথে থাকার জন্য। – WALL TOUCH, Hotline: 01712968571"
```

#### 2. Test Supplier Creation with SMS  
```php
// Go to Contact > Suppliers > Add
// Fill in the form with:
- Type: Supplier
- Name: করিম ট্রেডার্স  
- Mobile: 01812345678
- Save

Expected SMS: "Dear করিম ট্রেডার্স, আপনি সফলভাবে আমাদের Vendor List-এ যুক্ত হয়েছেন। সহযোগিতার জন্য আন্তরিক ধন্যবাদ। – WALL TOUCH, Hotline: 01712968571"
```

#### 3. Test Both Type Contact Creation
```php
// Go to Contact > Add
// Fill in the form with:
- Type: Both (Customer & Supplier)
- Name: সালিম এন্টারপ্রাইজ
- Mobile: 01912345679  
- Save

Expected SMS: "Dear সালিম এন্টারপ্রাইজ, আপনি আমাদের সিস্টেমে Customer ও Supplier হিসেবে যুক্ত হয়েছেন। সহযোগিতার জন্য ধন্যবাদ। – WALL TOUCH, Hotline: 01712968571"
```

### How to Test

1. **Configure SMS Settings First:**
   - Go to Business Settings
   - Configure your SMS gateway (Nexmo, Twilio, or Custom)
   - Save the settings

2. **Test Contact Creation:**
   - Navigate to Contact > Customers > Add or Contact > Suppliers > Add
   - Fill in the required information including mobile number
   - Click Save
   - Check if SMS is sent to the provided mobile number

3. **Check Logs:**
   - If SMS fails, check `storage/logs/laravel.log` for error messages
   - SMS errors are logged but don't prevent contact creation

### Troubleshooting

#### If SMS doesn't send:
1. Check if SMS is configured in business settings
2. Verify mobile number format
3. Check SMS gateway credentials
4. Review error logs in `storage/logs/laravel.log`

#### Common Issues:
- **"SMS not configured"**: Configure SMS settings in business setup
- **Gateway errors**: Verify API credentials and endpoint URLs
- **Bengali text issues**: Ensure UTF-8 encoding support in your SMS gateway

### SMS Flow Summary

1. **Contact Created** → Check if mobile number provided
2. **Determine Contact Type** → Customer, Supplier, or Both  
3. **Select Message Template** → Based on contact type
4. **Send SMS** → Using optimized SMS service
5. **Log Results** → Success/failure logged for debugging

The SMS functionality is now fully integrated and will automatically send appropriate welcome messages based on the type of contact being created.
