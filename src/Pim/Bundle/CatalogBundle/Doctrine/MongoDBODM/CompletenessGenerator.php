<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\MongoDBODM;

use Pim\Bundle\CatalogBundle\Doctrine\CompletenessGeneratorInterface;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Model\Completeness;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Factory\CompletenessFactory;
use Pim\Bundle\CatalogBundle\Validator\Constraints\ProductValueComplete;

use Symfony\Component\Validator\ValidatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\MongoDB\Query\Builder;
use Doctrine\MongoDB\Query\Expr;

/**
 * Generate the completeness when Product are in MongoDBODM
 * storage. Please note that the generation for several products
 * is done on the MongoDB via a JS generated by the application via HTTP.
 *
 * This generator is only able to generate completeness for one product
 *
 * @author    Benoit Jacquemont <benoit@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CompletenessGenerator implements CompletenessGeneratorInterface
{
    /**
     * @var DocumentManager;
     */
    protected $documentManager;

    /**
     * @var CompletenessFactory
     */
    protected $completenessFactory;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * Constructor
     *
     * @param DocumentManager     $documentManager
     * @param CompletenessFactory $completenessFactory
     * @param ValidatorInterface  $validator
     * @param string              $productClass
     * @param ChannelManager      $channelManager
     */
    public function __construct(
        DocumentManager $documentManager,
        CompletenessFactory $completenessFactory,
        ValidatorInterface $validator,
        $productClass,
        ChannelManager $channelManager
    ) {
        $this->documentManager     = $documentManager;
        $this->completenessFactory = $completenessFactory;
        $this->validator           = $validator;
        $this->productClass        = $productClass;
        $this->channelManager      = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForProduct(ProductInterface $product, $flush = true)
    {
        if (null === $product->getFamily()) {
            return;
        }

        $completenesses = $this->buildProductCompletenesses($product);

        foreach ($completenesses as $completeness) {
            $product->getCompletenesses()->add($completeness);
        }

        if ($flush) {
            $this->documentManager->flush($product);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForChannel(Channel $channel)
    {
        $this->generate(null, $channel);
    }

    /**
     * Build the completeness for the product
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function buildProductCompletenesses(ProductInterface $product)
    {
        $completenesses = array();

        $stats = $this->collectStats($product);

        foreach ($stats as $channelStats) {
            $channel = $channelStats['object'];
            $channelData = $channelStats['data'];
            $channelRequiredCount = $channelData['required_count'];

            foreach ($channelData['locales'] as $localeStats) {
                $completeness = $this->completenessFactory->build(
                    $channel,
                    $localeStats['object'],
                    $localeStats['missing_count'],
                    $channelRequiredCount
                );

                $completenesses[] = $completeness;
            }
        }

        return $completenesses;
    }

    /**
     * Generate statistics on the product completeness
     *
     * @param ProductInterface $product
     *
     * @return array $stats
     */
    protected function collectStats(ProductInterface $product)
    {
        $stats = array();
        $family = $product->getFamily();

        if (null === $family) {
            return $stats;
        }

        $channels = $this->channelManager->getFullChannels();

        foreach ($channels as $channel) {
            $channelCode = $channel->getCode();

            $stats[$channelCode]['object'] = $channel;
            $stats[$channelCode]['data'] = $this->collectChannelStats($channel, $product);
        }

        return $stats;
    }

    /**
     * Generate stats on product completeness for a channel
     *
     * @param Channel          $channel
     * @param ProductInterface $product
     *
     * @return array $stats
     */
    protected function collectChannelStats(Channel $channel, ProductInterface $product)
    {
        $stats = array();
        $locales = $channel->getLocales();
        $completeConstraint = new ProductValueComplete(array('channel' => $channel));
        $stats['required_count'] = 0;
        $stats['locales'] = array();
        $requirements = $product->getFamily()->getAttributeRequirements();

        foreach ($requirements as $req) {
            if (!$req->isRequired() || $req->getChannel() != $channel) {
                continue;
            }
            $stats['required_count']++;

            foreach ($locales as $locale) {
                $localeCode = $locale->getCode();

                if (!isset($stats['locales'][$localeCode])) {
                    $stats['locales'][$localeCode] = array();
                    $stats['locales'][$localeCode]['object'] = $locale;
                    $stats['locales'][$localeCode]['missing_count'] = 0;
                }

                $attribute = $req->getAttribute();
                $value = $product->getValue($attribute->getCode(), $localeCode, $channel->getCode());

                if (!$value || $this->validator->validateValue($value, $completeConstraint)->count() > 0) {
                    $stats['locales'][$localeCode]['missing_count'] ++;
                }
            }
        }

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissing()
    {
        $this->generate();
    }

    /**
     * Generate missing completenesses for a channel if provided or a product
     * if provided.
     *
     * @param Product $product
     * @param Channel $channel
     */
    protected function generate(ProductInterface $product = null, Channel $channel = null)
    {
        $productsQb = $this->documentManager->createQueryBuilder($this->productClass);

        $this->applyFindMissingQuery($productsQb, $product, $channel);

        $products = $productsQb->getQuery()->execute();

        foreach ($products as $product) {
            $this->generateMissingForProduct($product, false);
        }

        $this->documentManager->flush();
    }

    /**
     * Apply the query part to search for product where the completenesses
     * are missing. Apply only to the channel or product if provided.
     *
     * @param Builder $productsQb
     * @param Product $product
     * @param Channel $channel
     */
    protected function applyFindMissingQuery(
        Builder $productsQb,
        ProductInterface $product = null,
        Channel $channel = null
    ) {
        if (null !== $product) {
            $productsQb->field('_id')->equals($product->getId());
        } else {
            $combinations = $this->getChannelLocaleCombinations($channel);

            if (!empty($combinations)) {
                $orItems = new Expr();
                foreach ($combinations as $combination) {
                    $orItems->field('normalizedData.completenesses.'.$combination)->exists(false);
                }
                $productsQb->addOr($orItems);
            }
        }

        $productsQb->field('family')->notEqual(null);
    }

    /**
     * Generate a list of potential completeness value from existing channel
     * or from the provided channel
     *
     * @param Channel $channel
     *
     * @return array
     */
    protected function getChannelLocaleCombinations(Channel $channel = null)
    {
        $channels = array();
        $combinations = array();

        if (null !== $channel) {
            $channels = [$channel];
        } else {
            $channels = $this->channelManager->getFullChannels();
        }

        foreach ($channels as $channel) {
            $locales = $channel->getLocales();
            foreach ($locales as $locale) {
                $combinations[] = $channel->getCode().'-'.$locale->getCode();
            }
        }

        return $combinations;
    }

    /**
     * {@inheritdoc}
     */
    public function schedule(ProductInterface $product)
    {
        $product->getCompletenesses()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleForFamily(Family $family)
    {
        $productQb = $this->documentManager->createQueryBuilder($this->productClass);

        $productQb
            ->update()
            ->multiple(true)
            ->field('family')->equals($family->getId())
            ->field('completenesses')->unsetField()
            ->field('normalizedData.completenesses')->unsetField()
            ->getQuery()
            ->execute();
    }
}
