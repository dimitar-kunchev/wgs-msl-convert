<?php 

namespace Tests;

use WGSMSLConvertor\WGSMSLConvertor;
use PHPUnit\Framework\TestCase;

final class WGSMSLTest extends TestCase {
    public function testAll () {
        $conv = new WGSMSLConvertor();
        $this->assertInstanceOf(WGSMSLConvertor::class, $conv);
        
        $msl = $conv->WGSToMSL(42.6719739, 23.2886555, 624);
        $this->assertIsFloat($msl);
        $this->assertEqualsWithDelta(580, $msl, 4, 'Computed vs expected MSL differs too much');
        
        $wgs = $conv->MSLToWGS(42.6719739, 23.2886555, 580);
        $this->assertIsFloat($wgs);
        $this->assertEqualsWithDelta(624, $wgs, 4, 'Computed vs expected MSL differs too much');
    }
}