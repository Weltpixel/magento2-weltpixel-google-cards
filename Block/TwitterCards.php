<?php

namespace WeltPixel\GoogleCards\Block;

class TwitterCards extends GoogleCards
{

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getDescription($product)
    {
        $description = '';
        if ($this->_helper->getTwitterCardDescriptionType() == 1) {
            $description = $product->getData('description') ?? '';
        } elseif ($this->_helper->getTwitterCardDescriptionType() == 2){
            $description = $product->getData('meta_description') ?? '';
        } else {
            $description = $product->getData('short_description') ?? '';
        }

        // Remove widget tags to prevent structured data errors
        $description = $this->_helper->stripWidgets($description);

        return nl2br($description);
    }

    /**
     * @return string
     */
    public function getTwitterCreator()
    {
        return $this->_helper->getTwitterCreator();
    }

    /**
     * @return string
     */
    public function getTwitterSite()
    {
        return $this->_helper->getTwitterSite();
    }

    /**
     * @return string
     */
    public function getShippingCountry()
    {
        return $this->_helper->getTwitterShippingCountry();
    }

    /**
     * @return string
     */
    public function getTwitterCardType()
    {
        return $this->_helper->getTwitterCardType();
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        $priceOption = $this->_helper->getTwitterCardsPrice();
        return $this->_calculatePrice($priceOption);
    }
}
