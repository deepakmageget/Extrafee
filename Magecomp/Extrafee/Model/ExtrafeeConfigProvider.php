<?php
namespace Magecomp\Extrafee\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Model\Quote;

class ExtrafeeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magecomp\Extrafee\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $taxHelper;

    /**
     * @param \Magecomp\Extrafee\Helper\Data $dataHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magecomp\Extrafee\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magecomp\Extrafee\Helper\Tax $helperTax

    )
    {
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->taxHelper = $helperTax;
    }

    /**
     * @return array
     */
    public function getConfig()
    {

        $getProductFee = $this->dataHelper->getProductFee();
        $getProductFeeAmount = $this->dataHelper->getProductFeeAmount();
        $defaultFee = $this->dataHelper->getExtrafee();
        


        $ExtrafeeConfig = [];
        $enabled = $this->dataHelper->isModuleEnabled();
        $minimumOrderAmount = $this->dataHelper->getMinimumOrderAmount();
        $ExtrafeeConfig['fee_label'] = $this->dataHelper->getFeeLabel();
        $quote = $this->checkoutSession->getQuote();


        $getExtraProductSku = $this->dataHelper->getExtraProductSku();
        $getExtraProductSkuarray = explode(",",$getExtraProductSku);
   
        $cartProductQuantity = 0;
        $items = $quote->getAllItems();
        foreach($items as $item){
           
            $cartProductSku = $item->getSku();
            if(in_array($cartProductSku,$getExtraProductSkuarray)){
                $cartProductQuantity += (int)$item->getQty();
                $productsku = true;
                
            }else{
                $productsku = false;
            }
           
        }
        $getProductFee ? $fees = $getProductFeeAmount : $fees = $defaultFee;

        // $itemQty = $quote->getItemsQty();
        $fee = (int)$fees * (int)$cartProductQuantity;

        $subtotal = $quote->getSubtotal();
        $ExtrafeeConfig['custom_fee_amount'] = $fee;
        if ($this->taxHelper->isTaxEnabled() && $this->taxHelper->displayInclTax()) {
            $address = $this->_getAddressFromQuote($quote);
            $ExtrafeeConfig['custom_fee_amount'] = $fee + $address->getFeeTax();
        }
        if ($this->taxHelper->isTaxEnabled() && $this->taxHelper->displayBothTax()) {

            $address = $this->_getAddressFromQuote($quote);
            $ExtrafeeConfig['custom_fee_amount'] = $fee;
            $ExtrafeeConfig['custom_fee_amount_inc'] = $fee + $address->getFeeTax();

        }
        $ExtrafeeConfig['displayInclTax'] = $this->taxHelper->displayInclTax();
        $ExtrafeeConfig['displayExclTax'] = $this->taxHelper->displayExclTax();
        $ExtrafeeConfig['displayBoth'] = $this->taxHelper->displayBothTax();
        $ExtrafeeConfig['exclTaxPostfix'] = __('Excl. Tax');
        $ExtrafeeConfig['inclTaxPostfix'] = __('Incl. Tax');
        $ExtrafeeConfig['TaxEnabled'] = $this->taxHelper->isTaxEnabled();
        $ExtrafeeConfig['show_hide_Extrafee_block'] = ($enabled && ($minimumOrderAmount <= $subtotal) && $quote->getFee()) ? true : false;
        $ExtrafeeConfig['show_hide_Extrafee_shipblock'] = ($enabled && ($minimumOrderAmount <= $subtotal)) ? true : false;
        return $ExtrafeeConfig;
    }

    protected function _getAddressFromQuote(Quote $quote)
    {
        return $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
    }
}
