<?php

namespace Pim\Bundle\TransformBundle\Tests\Unit\Transformer\Property;

use Pim\Bundle\TransformBundle\Transformer\Property\PricesTransformer;

/**
 * Tests related class
 *
 * @author    Antoine Guigan <antoine@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PricesTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function getSetValuesData()
    {
        return array(
            'single_price' => array('currency', '25', array('currency' => 25)),
            'array'        => array(null, array('cur1' => '14', 'cur2' => '25'), array('cur1' => '14', 'cur2' => '25')),
            'string'       => array(null, '10 cur1, 12.2 cur2', array('cur1' => '10', 'cur2' => '12.2')),
            'null'         => array(null, null, array())
        );
    }

    /**
     * @dataProvider getSetValuesData
     */
    public function testSetValues($suffix, $data, $expectedPrices)
    {
        $columnInfo = $this->getMock('Pim\Bundle\TransformBundle\Transformer\ColumnInfo\ColumnInfoInterface');
        $columnInfo->expects($this->any())
            ->method('getSuffixes')
            ->will($this->returnValue(array($suffix)));

        $object = $this->getMockBuilder('Pim\Bundle\CatalogBundle\Model\ProductValueInterface')
            ->setMethods(array('setPrices', 'addPriceForCurrency', '__toString', 'setData', 'getData', 'getAttribute'))
            ->getMock();

        if (!$suffix) {
            $object->expects($this->once())
                ->method('setPrices')
                ->with($this->equalTo(array()));
        }
        $object->expects($this->exactly(count($expectedPrices)))
            ->method('addPriceForCurrency')
            ->will(
                $this->returnCallback(
                    function ($currency) use ($expectedPrices) {
                        $price = $this->getMockForPrice($currency, $expectedPrices[$currency]);
                        $price->expects($this->once())
                            ->method('setData')
                            ->with($this->equalTo($expectedPrices[$currency]));

                        return $price;
                    }
                )
            );

        $transformer = new PricesTransformer();
        $transformer->setValue($object, $columnInfo, $data);
    }

    /**
     * @expectedException \Pim\Bundle\TransformBundle\Exception\PropertyTransformerException
     * @expectedExceptionMessage Malformed price: "15"
     */
    public function testMalformedPrice()
    {
        $columnInfo = $this->getMock('Pim\Bundle\TransformBundle\Transformer\ColumnInfo\ColumnInfoInterface');
        $columnInfo->expects($this->any())
            ->method('getSuffixes')
            ->will($this->returnValue(array()));
        $object = $this->getMockBuilder('Pim\Bundle\CatalogBundle\Model\ProductValueInterface')
            ->setMethods(array('setPrices', 'addPriceForCurrency', '__toString', 'getData', 'setData', 'getAttribute'))
            ->getMock();

        $transformer = new PricesTransformer();
        $transformer->setValue($object, $columnInfo, '12.O2 cur1,15');
    }

    protected function getMockForPrice($currency, $data = null)
    {
        $price = $this->getMock('Pim\Bundle\CatalogBundle\Model\ProductPrice');
        $price->expects($this->any())
            ->method('getCurrency')
            ->will($this->returnValue($currency));
        if (null !== $data) {
            $price->expects($this->any())
                ->method('getData')
                ->will($this->returnValue($data));
        }

        return $price;
    }
}
