<?php

namespace WeltPixel\GoogleCards\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class GoogleCards extends \Magento\Framework\View\Element\Template
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $_imageBuilder;

    /**
     * @var \WeltPixel\GoogleCards\Helper\Data
     */
    protected $_helper;

    /**
     * Block factory
     *
     * @var \Magento\Review\Model\Review\SummaryFactory
     */
    protected $_reviewSummaryFactory;

    /**
     * @var CollectionFactory
     */
    protected $_reviewsFactory;

    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logo;

    /**
     * Review collection
     *
     * @var ReviewCollection
     */
    protected $_reviewsCollection;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Custom logo path
     */
    const CUSTOM_LOGO_PATH = 'weltpixel/google_logo';

    /**
     * GoogleCards constructor.
     * @param \Magento\Catalog\Block\Product\Context $productContext
     * @param \WeltPixel\GoogleCards\Helper\Data $helper
     * @param \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory
     * @param CollectionFactory $_reviewsFactory
     * @param \Magento\Theme\Block\Html\Header\Logo $logo
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $productContext,
        \WeltPixel\GoogleCards\Helper\Data $helper,
        \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $_reviewsFactory,
        \Magento\Theme\Block\Html\Header\Logo $logo,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_coreRegistry = $productContext->getRegistry();
        $this->_helper = $helper;
        $this->_reviewSummaryFactory = $reviewSummaryFactory;
        $this->_reviewsFactory = $_reviewsFactory;
        $this->_logo = $logo;
        $this->_productRepository = $productRepository;
        $this->_imageBuilder = $productContext->getImageBuilder();
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getDescription($product)
    {
        $description = '';
        if ($this->_helper->getDescriptionType() == 1) {
            $description = $product->getData('description') ?? '';
        } elseif ($this->_helper->getDescriptionType() == 2) {
            $description = $product->getData('meta_description') ?? '';
        } else {
            $description = $product->getData('short_description') ?? '';
        }

        // Remove widget tags to prevent Google structured data errors
        $description = $this->_helper->stripWidgets($description);

        return nl2br($description);
    }

    /**
     * @return mixed
     */
    public function getItemCondition($product)
    {
        $itemConditionAttribute = $this->_helper->getConfigItemCondition();
        $itemCondition = '';
        if ($itemConditionAttribute) {
            try {
                $attribute = $product->getResource()->getAttribute($itemConditionAttribute);
                if (!$attribute) {
                    return '';
                }
                $attributeSourceModel = $attribute->getSource();
                if ($attributeSourceModel instanceof \WeltPixel\GoogleCards\Model\Config\Source\ItemConditionsOptions) {
                    $itemCondition = $product->getData($itemConditionAttribute);
                } else {
                    $itemCondition = $product->getResource()->getAttribute($itemConditionAttribute)->getFrontend()->getValue($product);
                }

            } catch (\Exception $ex) {
                $itemCondition = '';
            }
        }

        return $itemCondition;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getBrand($product)
    {
        $brandAttribute = $this->_helper->getBrand();

        if ($brandAttribute == 'category_ids') {
            $categoryNames = [];
            $categories = $product->getCategoryCollection()->addAttributeToSelect('name');
            foreach ($categories as $category) {
                $categoryNames[] = $category->getName();
            }

            return implode(',', $categoryNames);
        }

        $brandName = '';
        if ($brandAttribute) {
            try {
                $brandName = $product->getAttributeText($brandAttribute);
                if (is_array($brandName) || !$brandName) {
                    $brandName = $product->getData($brandAttribute);
                }
            } catch (\Exception $ex) {
                $brandName = '';
            }
        }

        return $brandName;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getSku($product)
    {
        $skuAttribute = $this->_helper->getSku();
        $sku = '';
        if ($skuAttribute) {
            try {
                $sku = $product->getAttributeText($skuAttribute);
                if (is_array($sku) || !$sku) {
                    $sku = $product->getData($skuAttribute);
                }
            } catch (\Exception $ex) {
                $sku = '';
            }
        }
        return $sku;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getMpn($product)
    {
        $mpnAttribute = $this->_helper->getMpn();
        $mpnName = '';
        if ($mpnAttribute) {
            try {
                $mpnName = $product->getAttributeText($mpnAttribute);
                if (is_array($mpnName) || !$mpnName) {
                    $mpnName = $product->getData($mpnAttribute);
                }
            } catch (\Exception $ex) {
                $mpnName = '';
            }
        }
        return $mpnName;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getGtin($product)
    {
        $gtinAttribute = $this->_helper->getGtin();
        $gtinName = '';
        if ($gtinAttribute) {
            try {
                $gtinName = $product->getAttributeText($gtinAttribute);
                if (is_array($gtinName) || !$gtinName) {
                    $gtinName = $product->getData($gtinAttribute);
                }
            } catch (\Exception $ex) {
                $gtinName = '';
            }
        }
        return $gtinName;
    }

    /**
     * Get Store name
     * @return string
     */
    public function getStoreName()
    {
        $storeName = trim($this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? '');
        if ($storeName) {
            return $storeName;
        }
        return $this->_storeManager->getStore()->getName();
    }

    /**
     * @return mixed
     */
    public function getReviewsFormat()
    {
        return $this->_helper->getConfigReviewsFormat();
    }

    /**
     * @return \Magento\Review\Model\Review\Summary
     */
    public function getReviewSummary()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $reviewSummary = $this->_reviewSummaryFactory->create();
        $reviewSummary->setData('store_id', $storeId);
        $summaryModel = $reviewSummary->load($this->getProduct()->getId());

        return $summaryModel;
    }

    /**
     * Return limited reviews collection
     *
     * @return array|bool
     */
    public function getReviews()
    {
        $pageSize = $this->_helper->getConfigNumberOfReviews();
        if (null === $this->_reviewsCollection) {
            $this->_reviewsCollection = $this->_reviewsFactory->create()->addStoreFilter(
                $this->_storeManager->getStore()->getId()
            )->addStatusFilter(
                \Magento\Review\Model\Review::STATUS_APPROVED
            )->addEntityFilter(
                'product',
                $this->getProduct()->getId()
            )->setDateOrder();
        }

        $reviews = $this->_reviewsCollection->setPageSize($pageSize)->load()->addRateVotes();

        if ($reviews->getSize() == 0) {
            return false;
        }

        foreach ($reviews as $review) {
            $rateVotes = $review->getRatingVotes()->getItems();
            foreach ($rateVotes as $votes) {
                $vote = $votes->getValue();
                $review->setData('rating', $vote);
            }
        }
        return $reviews;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        $priceOption = $this->_helper->getGoogleCardsPrice();
        return $this->_calculatePrice($priceOption);
    }

    /**
     * format the date in schema readable format
     *
     * @param $date
     * @return false|string
     */
    public function formatedDate($date)
    {
        if (!$date) {
            return date('Y-m-d');
        }

        return date('Y-m-d', strtotime($date));
    }

    /**
     * @param string $priceOption
     * @return float
     */
    protected function _calculatePrice($priceOption)
    {
        $product = $this->getProduct();
        $productTYpe = $product->getTypeId();

        if ($productTYpe == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $simpleAssociatedProduct = null;
            $currentUrlOptions = parse_url($this->getCurrentUrl());
            $productAttributes = [];
            $attributesInfo = [];
            if (isset($currentUrlOptions['query'])) {
                $usedProductAttributes = $product->getData('_cache_instance_used_product_attributes');
                if (isset($usedProductAttributes)) {
                    $productAttributes = array_keys($productAttributes);
                }
                $attributeOptions = explode("&", $currentUrlOptions['query']);
                foreach ($attributeOptions as $attrOption) {
                    $attrKeyVal = explode("=", $attrOption);
                    if (in_array($attrKeyVal[0], $productAttributes)) {
                        $attributesInfo[$attrKeyVal[0]] = $attrKeyVal[1];
                    }
                }
            }
            try {
                $simpleAssociatedProduct = $product->getTypeInstance()->getProductByAttributes($attributesInfo, $product);
            } catch (\Exception $ex) {
            }

            if ($simpleAssociatedProduct) {
                $product = $simpleAssociatedProduct;
            }
        }

        $priceInfo = $product->getPriceInfo()->getPrice('final_price')->getAmount();
        $price = $priceInfo->getValue();
        /** Display of both prices incl. tax and excl. tax */
        if ((int)$this->_scopeConfig->getValue(
            'tax/display/type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) === 3
        ) {
            switch ($priceOption) {
                case 'incl_tax':
                    $price = $priceInfo->getValue();
                    break;
                case 'excl_tax':
                    $price = $priceInfo->getValue('tax');
                    break;
            }
        }

        return number_format($price, 2, '.', '');
    }

    /**
     * @return mixed
     */
    public function getFacebookUrl()
    {
        return $this->_helper->getFacebookUrlConf();
    }

    /**
     * @return mixed
     */
    public function getTwitterUrl()
    {
        return $this->_helper->getTwitterUrlConf();
    }

    /**
     * @return mixed
     */
    public function getInstagramUrl()
    {
        return $this->_helper->getInstagramUrlConf();
    }

    /**
     * @return mixed
     */
    public function getYoutubeUrl()
    {
        return $this->_helper->getYoutubeUrlConf();
    }

    /**
     * @return mixed
     */
    public function getLinkedinUrl()
    {
        return $this->_helper->getLinkedinUrlConf();
    }

    /**
     * @return mixed
     */
    public function getMyspaceUrl()
    {
        return $this->_helper->getMyspaceUrlConf();
    }

    /**
     * @return mixed
     */
    public function getPinterestUrl()
    {
        return $this->_helper->getPinterestUrlConf();
    }

    /**
     * @return mixed
     */
    public function getSoundcloudUrl()
    {
        return $this->_helper->getSoundcloudUrlConf();
    }

    /**
     * @return mixed
     */
    public function getThumblrUrl()
    {
        return $this->_helper->getThumblrUrlConf();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $shortVersion
     * @return string
     */
    public function getProductAvailability($product, $shortVersion = false)
    {

        $inStockValue = ($shortVersion) ? "in stock" : "https://schema.org/InStock";
        $outOfStockValue = ($shortVersion) ? "out of stock" : "https://schema.org/OutOfStock";

        $productAvailability = $outOfStockValue;
        if ($product->isAvailable()) {
            $productAvailability = $inStockValue;
        }

        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
           return $productAvailability;
        }

        $productStockItem = $product->getExtensionAttributes()->getStockItem();
        if (!$productStockItem) {
            $product = $this->_productRepository->getById($product->getId());
            $productStockItem = $product->getExtensionAttributes()->getStockItem();
        }
        $backOrdersStatus = $productStockItem->getBackorders();

        if ($backOrdersStatus != \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO) {
            $productQuantity = $productStockItem->getQty();
            if ($productQuantity > 0) {
                $productAvailability = $inStockValue;
            } else {
                $productAvailability = $outOfStockValue;
            }
        }

        return $productAvailability;
    }
}
