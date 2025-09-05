<?php
/**
 * Mobile Detect Library
 * A lightweight PHP class for detecting mobile devices (including tablets).
 *
 * @license https://github.com/serbanghita/Mobile-Detect/blob/master/LICENSE.txt MIT License
 * @author Serban Ghita <serbanghita@gmail.com>
 * @version 2.8.34
 */
class Mobile_Detect
{
    protected $userAgent;
    protected $httpHeaders;
    protected $isMobile = null;
    protected $isTablet = null;

    public function __construct($userAgent = null, $httpHeaders = null)
    {
        $this->userAgent = $userAgent ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        $this->httpHeaders = $httpHeaders ?: $this->getHttpHeaders();
    }

    protected function getHttpHeaders()
    {
        return array(
            'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '',
            'HTTP_X_OPERAMINI_PHONE' => isset($_SERVER['HTTP_X_OPERAMINI_PHONE']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE'] : '',
            'HTTP_X_DEVICE_USER_AGENT' => isset($_SERVER['HTTP_X_DEVICE_USER_AGENT']) ? $_SERVER['HTTP_X_DEVICE_USER_AGENT'] : '',
            'HTTP_X_WAP_PROFILE' => isset($_SERVER['HTTP_X_WAP_PROFILE']) ? $_SERVER['HTTP_X_WAP_PROFILE'] : '',
            'HTTP_PROFILE' => isset($_SERVER['HTTP_PROFILE']) ? $_SERVER['HTTP_PROFILE'] : '',
        );
    }

    public function isMobile()
    {
        if ($this->isMobile === null) {
            $this->isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WPDesktop/', $this->userAgent);
        }
        return $this->isMobile;
    }

    public function isTablet()
    {
        if ($this->isTablet === null) {
            $this->isTablet = preg_match('/Tablet|iPad|PlayBook|Silk/', $this->userAgent);
        }
        return $this->isTablet;
    }

    public function isIPhone() {
        return strpos($this->userAgent, 'iPhone') !== false;
    }

    public function isAndroid() {
        return strpos($this->userAgent, 'Android') !== false;
    }

    public function isSamsung() {
        return strpos($this->userAgent, 'Samsung') !== false;
    }

    public function isHuawei() {
        return strpos($this->userAgent, 'Huawei') !== false || strpos($this->userAgent, 'HUAWEI') !== false;
    }
    
    public function isXiaomi() {
        return strpos($this->userAgent, 'Xiaomi') !== false || strpos($this->userAgent, 'Redmi') !== false;
    }
    
    public function isOnePlus() {
        return strpos($this->userAgent, 'OnePlus') !== false;
    }
    
    public function isSony() {
        return strpos($this->userAgent, 'Sony') !== false || strpos($this->userAgent, 'Xperia') !== false;
    }
    
    public function isLG() {
        return strpos($this->userAgent, 'LG') !== false;
    }
    
    public function isGooglePixel() {
        return strpos($this->userAgent, 'Pixel') !== false;
    }
    
    public function isOppo() {
        return strpos($this->userAgent, 'Oppo') !== false || strpos($this->userAgent, 'OPPO') !== false;
    }
    
    public function isVivo() {
        return strpos($this->userAgent, 'Vivo') !== false || strpos($this->userAgent, 'VIVO') !== false;
    }

    public function isNothing() {
        return strpos($this->userAgent, 'Nothing') !== false;
    }
    
    public function isTabletOrMobile() {
        return $this->isMobile() || $this->isTablet();
    }
    // Add more device checks as needed
}