<?php 

namespace Tests;

use WGSMSLConvertor\EGM96;
use PHPUnit\Framework\TestCase;

final class EGMTest extends TestCase {
    public function testLoad () {
        $egm = new EGM96();
        $this->assertInstanceOf(EGM96::class, $egm);
    }
    
    public function testCoordinates() {
        $egm = new EGM96();
        
        $lat_lng_wgs = [
            [42.6719739,    23.2886555,     624.299     , 580.103],
            [48.242294,     16.1226793,     355.01      , 309.617],
            [25.7706823,    -103.6330612,   1127.477    , 1147.947],
            [45.6833696,    -74.0259672,    47.46       , 79.458],
            [-32.0376611,   116.0105222,    -4.622      , 26.652],
            [52.3817471,    8.8072269,      102.475     , 58.969],
            [-6.2167085,    106.6725155,    39.376      , 21.752],
            [45.6827915,    -74.0268506,    48.833      , 80.831],
            [-32.0376611,   116.0105222,    -4.622      , 26.652],
            [-6.9003184,    107.6452816,    720.291     , 698.816],
            [38.7579828,    -121.2914495,   32.22       , 61.965],
            [0.0001,        -0.9981,        1           , -16.305],
            [42.6658763,    23.2883291,     638         , 593.79],
        ];
        
        foreach ($lat_lng_wgs as $t) {
            $correction = $egm->altitudeCorrectionAt($t[0], $t[1]);
            $msl = $t[2] - $correction;
            $this->assertIsFloat($correction);
            $this->assertThat($correction, $this->logicalNot($this->equalTo(0)), 'Correction should not be zero!');
            $this->assertEqualsWithDelta($t[3], $msl, 5, 'Computed vs expected MSL differs too much for coordinates '. $t[0].' '.$t[1]);
            //echo $t[0], ' ', $t[1], ' WGS ', $t[2], ' Corr ', $correction, ' MSL ', $msl, "\n";
        }
    }
}