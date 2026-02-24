<?php

namespace BPMpesaGateway\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGUtils
{
    // Decrypt when reading
    public static function encrypt_credential($value)
    {
        if (empty($value)) return '';
        return base64_encode(openssl_encrypt($value, 'AES-256-CBC', wp_salt('auth'), 0, substr(wp_salt('nonce'), 0, 16)));
    }

    public static function decrypt_credential($value)
    {
        if (empty($value)) return '';
        return openssl_decrypt(base64_decode($value), 'AES-256-CBC', wp_salt('auth'), 0, substr(wp_salt('nonce'), 0, 16));
    }

    /**
     * Safaricom IP ranges for validating incoming requests to the callback endpoint.
     * 
     * @return array List of Safaricom IP ranges in CIDR notation.
     */
    public static function safaricom_ips()
    {
        return [
            '196.201.212.0/22',   // Covers most Safaricom IPs
            '196.201.214.0/24',
        ];
    }

    /**
     * Validate if the incoming request is from a Safaricom IP address.
     * 
     * @param string $ip The IP address to validate.
     * @return bool True if the IP is from Safaricom, false otherwise.
     */
    public static function is_safaricom_ip($ip)
    {
        $safaricom_ips = self::safaricom_ips();
        foreach ($safaricom_ips as $range) {
            if (self::ip_in_range($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is within a specific CIDR range.
     * 
     * @param string $ip The IP address to check.
     * @param string $range The CIDR range to check against
     * @return bool True if the IP is within the range, false otherwise.
     */
    private static function ip_in_range($ip, $range)
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) === $subnet;
    }

     // rate limit endpoints
    public static function rate_limit_exceeded($ip, $phone_number, $max_request = 5, $time_window = 2, $ban_threshold = 8)
    {
        $base_key   = 'bpmg_rate_limit_' . md5($ip . '_' . $phone_number);
        $count_key  = $base_key . '_count';
        $ban_key    = $base_key . '_ban';

        // If already banned, reject immediately without touching the count
        if (get_transient($ban_key) !== false) {
            return true;
        }

        $request_count = (int) get_transient($count_key);

        // First request in this window
        if ($request_count === 0) {
            set_transient($count_key, 1, $time_window * MINUTE_IN_SECONDS);
            return false;
        }

        $new_count = $request_count + 1;

        // Escalate to a ban — use a separate key so the 1-hour window is stable
        if ($new_count >= $ban_threshold) {
            set_transient($ban_key, 1, HOUR_IN_SECONDS);
            delete_transient($count_key);
            return true;
        }

        // Soft limit — still within the original window
        if ($new_count > $max_request) {
            set_transient($count_key, $new_count, $time_window * MINUTE_IN_SECONDS);
            return true;
        }

        set_transient($count_key, $new_count, $time_window * MINUTE_IN_SECONDS);
        return false;
    }

    public static function check_phone_number($phone)
    {
        $preg_match = '/^254(7(?:[0129][0-9]|4[0-3568]|5[7-9]|6[89])|11[0-5])\d{6}$/';

        /** @disregard P1010 Undefined type */
        return preg_match($preg_match, $phone);
    }
}
