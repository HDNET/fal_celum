<?php

declare(strict_types=1);

namespace HDNET\FalCelum;

class Encryption
{
    private const SECRET_KEY = 'ZbMchtd9DivzjPDi5QIio1iVERFnNZiSE33QKY3Gw9rYfCNLFiKloJQt3zi4';

    public function decrypt($licenseKey)
    {
        $sResult = '';
        $licenseKey = $this->decode_base64($licenseKey);
        for ($i = 0; $i < strlen($licenseKey); $i++) {
            $sChar = substr($licenseKey, $i, 1);
            $sKeyChar = substr(self::SECRET_KEY, ($i % strlen(self::SECRET_KEY)) - 1, 1);
            $sChar = chr(ord($sChar) - ord($sKeyChar));
            $sResult .= $sChar;
        }
        return $sResult;
    }

    protected function decode_base64($licenseKey)
    {
        $sBase64 = strtr($licenseKey, '-_', '+/');
        return base64_decode($sBase64 . '==');
    }
}
