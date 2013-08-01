<?php
namespace RTP\RtpImgquery\Tests\Utility;

use RTP\RtpImgquery\Utility\Collection;

class CollectionTest extends \Tx_Phpunit_TestCase
{

    /**
     * @test
     * @dataProvider arrayValuesAreTrimmedDataProvider
     * @param $actual
     * @param $expected
     */
    public function testArrayValuesAreTrimmed($actual, $expected)
    {
        $this->assertEquals($expected, Collection::trimMembers($actual));
    }

    /**
     * Data provider for testArrayValuesAreTrimmed
     *
     * @return array
     */
    public static function arrayValuesAreTrimmedDataProvider()
    {
        $value = 'value';
        $untrimmed = str_pad($value, rand(10, 20), chr(32), STR_PAD_BOTH);
        return array(
            'stringsAreTrimmed' => array(
                array('key' => $untrimmed),
                array('key' => $value)
            ),
            'emptyStringsAreNotRemoved' => array(
                array(' ', 'Some Text', '', 'More Text'),
                array(0 => '', 1 => 'Some Text', 2 => '', 3 =>  'More Text')
            ),
            'arraysRemainUntouched' => array(
                array(array(), array('', '')),
                array(0 => array(), 1 => array('', ''))
            )
        );
    }

    /**
     * @test
     * @dataProvider arrayValuesAreStrippedDataProvider
     * @param $actual
     * @param $expected
     */
    public function testArrayValuesAreStripped($actual, $expected)
    {
        $this->assertEquals($expected, Collection::stripEmpty($actual));
    }

    /**
     * Data provider for testArrayValuesAreStripped
     *
     * @return array
     */
    public static function arrayValuesAreStrippedDataProvider()
    {
        return array(
            'zeroLengthStringsAreRemoved' => array(
                array(0 => ' ', 1 => 'Some Text', 2 => '', 3 => 'More Text'),
                array(0 => ' ', 1 => 'Some Text', 3 => 'More Text'),
            ),
            'nullIsRemoved' => array(
                array(0 => null, 1 => 'Some Text', 2 => null, 3 => 'More Text'),
                array(1 => 'Some Text', 3 =>  'More Text')
            ),
            'emptyArraysAreRemoved' => array(
                array(0 => array(), 1 => array()),
                array()
            ),
            'nonEmptyArraysAreNotRemoved' => array(
                array(0 => array(0 => '', 1 => false), 1 => array(''), 2 => array(null)),
                array(0 => array(0 => '', 1 => false), 1 => array(''), 2 => array(null))
            ),

        );
    }

    /**
     * @test
     * @dataProvider explodingOfStringsDataProvider
     * @param $actual
     * @param $expected
     * @param string $delimiter
     * @param bool $onlyNonEmptyValues
     * @param int $limit
     */
    public function testExplodingOfStrings(
        $actual,
        $expected,
        $delimiter = ',',
        $onlyNonEmptyValues = true,
        $limit = 0
    ) {
        $this->assertEquals($expected, Collection::trimExplode($actual, $delimiter, $onlyNonEmptyValues, $limit));
    }

    /**
     * @return array
     */
    public static function explodingOfStringsDataProvider()
    {
        return array(
            'stringIsConvertedToArray' => array(
                '1,two,3,4,null',
                array(1,'two',3,4,'null'),
            ),
            'alternateDelimiterIsApplied' => array(
                '1|two|3|4|null',
                array(1,'two',3,4,'null'),
                '|'
            ),
            'emptyStringsAreNotRemoved' => array(
                '1,two,3, , , ,4,null',
                array(0 => 1, 1 => 'two', 2 => 3, 3 => '', 4 => '', 5 => '', 6 => 4, 7 => 'null'),
                ',',
                false
            ),
            'firstThreeItemsAreReturned' => array(
                '1,2,3,4,5,6,7',
                array(0 => 1, 1 => 2, 2 => 3),
                ',',
                true,
                3
            ),
            'lastTwoItemsAreReturned' => array(
                '1,2,3,4,5,6,7',
                array(0 => 6, 1 => 7),
                ',',
                true,
                -2
            ),
            'limitLargerThanCountHasNoEffect' => array(
                '1,2,3,4,5,6,7',
                array(0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 7),
                ',',
                true,
                10
            ),
            'emptyStringReturnsEmptyArray' => array(
                '',
                array()
            ),
            'otherTypesReturnEmptyArray' => array(
                new \stdClass(),
                array()
            )
        );
    }
}
