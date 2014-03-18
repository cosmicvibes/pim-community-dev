<?php

namespace Pim\Bundle\CatalogBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Pim\Bundle\FlexibleEntityBundle\Model\AbstractFlexibleValue;
use Pim\Bundle\FlexibleEntityBundle\Model\AbstractAttributeOption;

/**
 * Value for an attribute
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ExclusionPolicy("all")
 */
class ProductValue extends AbstractFlexibleValue implements ProductValueInterface
{
    /**
     * @var ProductInterface $entity
     */
    protected $entity;

    /**
     * Store varchar value
     * @var string $varchar
     */
    protected $varchar;

    /**
     * Store integer value
     * @var integer $integer
     */
    protected $integer;

    /**
     * Store decimal value
     * @var double $decimal
     */
    protected $decimal;

    /**
     * Store boolean value
     * @var boolean $boolean
     */
    protected $boolean;

    /**
     * Store text value
     * @var string $text
     */
    protected $text;

    /**
     * Store date value
     * @var date $date
     */
    protected $date;

    /**
     * Store datetime value
     * @var date $datetime
     */
    protected $datetime;

    /**
     * Store options values
     *
     * @var ArrayCollection options
     */
    protected $options;

    /**
     * Store simple option value
     *
     * @var Pim\Bundle\CatalogBundle\Entity\AttributeOption $option
     */
    protected $option;

    /**
     * Store upload values
     *
     * @var Media $media
     */
    protected $media;

    /**
     * Store metric value
     *
     * @var Metric $metric
     */
    protected $metric;

    /**
     * Store prices value
     *
     * @var ArrayCollection $prices
     */
    protected $prices;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->prices = new ArrayCollection();
    }

    /**
     * Remove an option
     *
     * @param AbstractAttributeOption $option
     *
     * @return ProductValue
     */
    public function removeOption(AbstractAttributeOption $option)
    {
        $this->options->removeElement($option);

        return $this;
    }

    /**
     * Get media
     *
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set media
     *
     * @param Media $media
     *
     * @return ProductValue
     */
    public function setMedia(Media $media)
    {
        $media->setValue($this);
        $this->media = $media;

        return $this;
    }

    /**
     * Get metric
     *
     * @return Metric
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * Set metric
     *
     * @param Metric $metric
     *
     * @return ProductValue
     */
    public function setMetric($metric)
    {
        $this->metric = $metric;

        return $this;
    }

    /**
     * Get prices
     *
     * @return array
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * Get the price matching the given currency
     *
     * @param string $currency
     *
     * @return boolean|Price
     */
    public function getPrice($currency)
    {
        return isset($this->prices[$currency]) ? $this->prices[$currency] : null;
    }

    /**
     * Set prices, used for multi select to retrieve many options
     *
     * @param ArrayCollection $prices
     *
     * @return ProductValue
     */
    public function setPrices($prices)
    {
        foreach ($prices as $price) {
            $this->addPrice($price);
        }

        return $this;
    }

    /**
     * Add price
     *
     * @param ProductPrice $price
     *
     * @return ProductValue
     */
    public function addPrice(ProductPrice $price)
    {
        $this->prices[$price->getCurrency()] = $price;
        $price->setValue($this);

        return $this;
    }

    /**
     * Adds a price for the given currency, or returns the existing price
     *
     * @param string $currency
     *
     * @return ProductPrice
     *
     * @deprecated This method will be removed in 1.2, use ProductBuilder::addPriceForCurrency() instead
     */
    public function addPriceForCurrency($currency)
    {
        $prices = $this->getPrices();
        if (!isset($prices[$currency])) {
            $this->addPrice(new ProductPrice(null, $currency));
        }

        return $this->prices[$currency];
    }

    /**
     * Remove price
     *
     * @param ProductPrice $price
     *
     * @return ProductValue
     */
    public function removePrice(ProductPrice $price)
    {
        $this->prices->remove($price->getCurrency());

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRemovable()
    {
        if (null === $this->entity) {
            return true;
        }

        return $this->entity->isAttributeRemovable($this->attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $data = $this->getData();

        if ($data instanceof \DateTime) {
            $data = $data->format(\DateTime::ISO8601);
        }

        if ($data instanceof \Doctrine\Common\Collections\Collection) {
            $items = array();
            foreach ($data as $item) {
                $value = (string) $item;
                if (!empty($value)) {
                    $items[] = $value;
                }
            }

            return implode(', ', $items);
        } elseif (is_object($data)) {
            return (string) $data;
        }

        return (string) $data;
    }
}
