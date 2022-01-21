<?php 

namespace WGSMSLConvertor;

class EGM96 {
    private $width = 0;
    private $height = 0;
    private $data = [];
    private $offset = 0;
    private $scale = 0;
    private $originNorth = 0;
    private $originEast = 0;
    
    private function loadOriginValue($str) {
        $c = $str[strlen($str) - 1];
        $p = floatval(substr($str, 0, strlen($str) - 1));
        switch ($c) {
            case 'N': $this->originNorth = $p; break;
            case 'E': $this->originEast = $p; break;
            case 'S': $this->originNorth = $p - 180; break;
            case 'W': $this->originEasy = $p - 360; break;
            default:
                throw new \Exception('Error in egm file header - uknown origin format '.$str);
        }
    }
    
    public function __construct () {
        $file_path = dirname(__FILE__).'/../../data/egm96-15.pgm';
        
        $f = fopen($file_path, 'r');
        
        $identification_line = trim(fgets($f));
        if ($identification_line != 'P5') {
            throw new \Exception('Error in egm file header - invalid id ('.$identification_line.')');
        }
        $next_line = '';
        while (!feof($f)) {
            $next_line = trim(fgets($f));
            if ($next_line[0] == '#') {
                $next_line = substr($next_line, 2);
                list($key, $val) = explode(' ', $next_line, 2);
                switch ($key) {
                    case 'Offset': $this->offset = floatval($val); break;
                    case 'Scale':
                        $this->scale = floatval($val); 
                        break;
                    case 'Origin':
                        $tmp = explode(' ', $val, 2);
                        $this->loadOriginValue($tmp[0]);
                        $this->loadOriginValue($tmp[1]);
                        break;
                    case 'Vertical_Datum': 
                        if ($val != 'WGS84') {
                            throw new \Exception('Error in egm file header - invalid vertical datum (' + $val + ')');
                        }
                        break;
                }
            } else {
                break;
            }
        }
        if ($next_line) {
            list($this->width, $this->height) = explode(' ', $next_line, 2);
        }
        $max_val_line = trim(fgets($f));
        if ($max_val_line != '65535') {
            throw new \Exception('Error in egm file header - invalid max value (' + $vax_val_line + ')');
        }
        
        if ($this->width == 0 || $this->height == 0) {
            throw new \Exception ('Error in egm file - 0x0 size');
        }
        if ($this->scale == 0) {
            throw new \Exception ('Error in egm file - zero scale');
        }
        
        
        for ($x = 0; $x < $this->width; $x ++) {
            $this->data[$x] = [];
        }
        for ($y = 0; $y < $this->height; $y ++) {
            for ($x = 0; $x < $this->width; $x ++) {
                $b = fread($f, 2);
                if (strlen($b) != 2) {
                    throw new \Exception('Error in egm file - not enough bytes');
                }
                $this->data[$x][$y] = (ord($b[0]) << 8) + ord($b[1]);
            }
        }
        
        if (empty($this->data)) {
            throw new \Exception ('Error in egm file - no data loaded');
        }
        
        fclose($f);
    }
    
    private static function CubicInterpolate1D($v0, $v1, $v2, $v3, $frac): float {
        $A = -0.5 * $v0 + 1.5 * $v1 - 1.5 * $v2 + 0.5 * $v3;
        $B = $v0 - 2.5 * $v1 + 2 * $v2 - 0.5 * $v3;
        $C = -0.5 * $v0 + 0.5 * $v2;
        $D = $v1;
        
        return $D + $frac * ($C + $frac * ($B + $frac * $A));
    }
    
    private static function CubicInterpolate2D($ndata, $x, $y): float {
        $x1 = self::CubicInterpolate1D($ndata[0][0], $ndata[1][0], $ndata[2][0], $ndata[3][0], $x);
        $x2 = self::CubicInterpolate1D($ndata[0][1], $ndata[1][1], $ndata[2][1], $ndata[3][1], $x);
        $x3 = self::CubicInterpolate1D($ndata[0][2], $ndata[1][2], $ndata[2][2], $ndata[3][2], $x);
        $x4 = self::CubicInterpolate1D($ndata[0][3], $ndata[1][3], $ndata[2][3], $ndata[3][3], $x);
        
        return self::CubicInterpolate1D($x1, $x2, $x3, $x4, $y);
    }
    
    public function altitudeCorrectionAt($lat, $lng) : float {
        $lngN = 360 + $lng - $this->originEast;
        if ($lngN > 360) $lngN -= 360;
        $tgx = $lngN * $this->width / 360.0;
        
        $latN = ($this->originNorth - $lat) + 180;
        if ($latN > 180) $latN -= 180;
        $tgy = $latN * $this->height / 180.0;
        
        // echo $lat, " ", $lng, " ", $lngN, " ", $latN, " ", $tgx, " ", $tgy, "\n";  
        
        // tgx and tgy are now "coordinates" of the data. We keep them as floating point as we proceed with reading surrounding values and doing interpolations
        
        $intx = floor($tgx);
        $inty = floor($tgy);
        $fracx = $tgx - $intx;
        $fracy = $tgy - $inty;
        
        $region = [[0,0,0,0],[0,0,0,0],[0,0,0,0],[0,0,0,0]];
        
        for ($x = 0; $x < 4; $x++)
        {
            for ($y = 0; $y < 4; $y++)
            {
                $region[$x][$y] = $this->data[($intx + $x - 1 + $this->width) % $this->width][($inty + $y - 1 + $this->height) % $this->height];
            }
        }
        
        // echo "\n", $fracx, " ", $fracy, " \n";
        
        return $this->offset + self::CubicInterpolate2D($region, $fracx, $fracy) * $this->scale; 
    }
}