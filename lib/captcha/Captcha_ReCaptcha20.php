<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Captcha_ReCaptcha20 extends Laminas\Captcha\ReCaptcha
{
    protected $RESPONSE  = 'g-recaptcha-response';

    /**
     * Validate captcha
     *
     * @see    Zend_Validate_Interface::isValid()
     * @param  mixed      $value
     * @param  array|null $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        if (! is_array($value) && ! is_array($context)) {
            $this->error(self::MISSING_VALUE);
            return false;
        }

        if (empty($value[$this->RESPONSE])) {
            $this->error(self::MISSING_VALUE);
            return false;
        }

        if (! extension_loaded('curl')) {
            $this->error('reCAPTCHA 2 requires the PHP CURL extension');
            return false;
        }

        // Google request was cached
        if (is_array($_SESSION['recaptcha_cache']) && in_array($value[$this->RESPONSE], $_SESSION['recaptcha_cache'])) {
            return true;
        }

        //set POST variables
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $fields = [
            'secret' => urlencode($this->getSecretKey()),
            'response' => urlencode($value[$this->RESPONSE]),
            'remoteip' => urlencode($_SERVER['REMOTE_ADDR']),
        ];

        $fields_string = '';
        foreach ($fields as $k => $v) {
            $fields_string .= $k . '=' . $v . '&';
        }
        rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = @json_decode(curl_exec($ch), true);

        if (! is_array($result)) {
            $this->error(self::ERR_CAPTCHA);
            return false;
        }

        if ($result['success'] == false) {
            $this->error(self::BAD_CAPTCHA);
            return false;
        }

        // Cache google respnonse to avoid second resubmission on ajax form
        $_SESSION['recaptcha_cache'][] = $value[$this->RESPONSE];

        return true;
    }

    /**
     * Render captcha
     *
     * @return string
     */
    public function render()
    {
        TikiLib::lib('header')->add_css('.g-recaptcha-response {display:none !important;}');
        return '<div class="g-recaptcha" data-sitekey="' . $this->getSiteKey() . '" id="antibotcode"></div>';
    }

    /**
     * Render captcha though Ajax
     *
     * @return string
     */
    public function renderAjax()
    {
        static $id = 1;
        TikiLib::lib('header')->add_js("
                grecaptcha.render('g-recaptcha{$id}', {
                'sitekey': '{$this->getSiteKey()}'
                });
                ", 100);
        return '<div id="g-recaptcha' . $id . '"></div>';
    }
}
