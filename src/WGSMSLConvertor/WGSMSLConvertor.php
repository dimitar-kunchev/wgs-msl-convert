<?php 

namespace WGSMSLConvertor;

class WGSMSLConvertor {
    private $egm;
    public function  __construct() {
        $this->egm = new EGM96();
    }
    
    public function WGSToMSL($lat, $lng, $altitude): float {
        $correction = $this->egm->altitudeCorrectionAt($lat, $lng);
        return $altitude - $correction;
    }
    
    public function MSLToWGS($lat, $lng, $altitude): float {
        $correction = $this->egm->altitudeCorrectionAt($lat, $lng);
        return $altitude + $correction;
    }
}