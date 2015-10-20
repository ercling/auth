<?php
namespace base\helpers;

class Security
{
    public static function generateRandomString($length = 32)
    {
        $bytes = self::generateRandomKey($length);
        // '=' character(s) returned by base64_encode() are always discarded because
        // they are guaranteed to be after position $length in the base64_encode() output.
        return strtr(substr(base64_encode($bytes), 0, $length), '+/', '_-');
    }

    public static function generateRandomKey($length = 32)
    {
        $bytes = '';
        if (!extension_loaded('openssl')) {
            throw new \ErrorException('The OpenSSL PHP extension is not installed.');
        }
        $bytes .= openssl_random_pseudo_bytes($length, $cryptoStrong);
        return mb_substr($bytes, 0, $length, '8bit');
    }
}