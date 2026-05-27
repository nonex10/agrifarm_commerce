<?php
/**
 * eSewa Payment Gateway – Configuration
 * ======================================
 * File: api/esewa/config.php
 *
 * HOW TO SWITCH TO PRODUCTION:
 *   1. Change ESEWA_ENV to 'production'
 *   2. Replace ESEWA_PRODUCT_CODE with your real Merchant Code from eSewa
 *   3. Replace ESEWA_SECRET_KEY   with your real Secret Key from eSewa
 *   4. Update BASE_URL in api/config.php to your live domain
 */

/* ── Environment ─────────────────────────────────────────── */
define('ESEWA_ENV', 'sandbox'); // 'sandbox' | 'production'

if (ESEWA_ENV === 'production') {

    /* ── PRODUCTION credentials (replace before going live) ── */
    define('ESEWA_PAYMENT_URL', 'https://epay.esewa.com.np/api/epay/main/v2/form');
    define('ESEWA_STATUS_URL',  'https://epay.esewa.com.np/api/epay/transaction/status/');
    define('ESEWA_PRODUCT_CODE', 'YOUR_MERCHANT_CODE');   // e.g. "AGRIFRESH"
    define('ESEWA_SECRET_KEY',   'YOUR_LIVE_SECRET_KEY'); // from eSewa merchant dashboard

} else {

    /* ── SANDBOX / UAT credentials (official eSewa test values) */
    define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
    define('ESEWA_STATUS_URL',  'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
    define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
    define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');
    // UAT test eSewa account: 9806800001 / 9806800002 / 9806800003
    // UAT test MPIN / Password: Nepal@123
}