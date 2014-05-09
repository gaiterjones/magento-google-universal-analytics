<?php
/**
 * GAITERJONES
 *		v1.0.1
 * 		Universal Analytics Update for Magento 1.3.X - 1.4.0
 *		https://developers.google.com/analytics/devguides/collection/analyticsjs/advanced
 *
 *
 *
 */


/**
 * GoogleAnalytics Page Block
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_GoogleAnalytics_Block_Ga extends Mage_Core_Block_Text
{
 
	protected function _getPageTrackingCode($accountId)
	{
		$hostName=trim($this->getHostName());
		$pageName   = trim($this->getPageName());
		
		$optPageURL = '';
		if ($pageName && preg_match('/^\/.*/i', $pageName)) {
			$optPageURL = ", '{$this->jsQuoteEscape($pageName)}'";
		}
		return "
			ga('create', '".$this->jsQuoteEscape($accountId)."', '".$hostName."');
			ga('send', 'pageview' ".$optPageURL.");
		";
	}
	
	protected function _getOrdersTrackingCode()
	{

        $quote = $this->getQuote();
		
        if (!$quote) {
            return '';
        }

        if ($quote instanceof Mage_Sales_Model_Quote) {
            $quoteId = $quote->getId();
        } else {
            $quoteId = $quote;
        }

        if (!$quoteId) {
            return '';
        }
		
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToFilter('quote_id', $quoteId)
            ->load();

			foreach ($orders as $order)
			{
					if (!$order) {
						return '';
					}

					if (!$order instanceof Mage_Sales_Model_Order) {
						$order = Mage::getModel('sales/order')->load($order);
					}

					if (!$order) {
						return '';
					}
						
					$result = array("
						// Transaction code...
						ga('require', 'ecommerce', 'ecommerce.js');
					");

				
					if ($order->getIsVirtual()) {
						$address = $order->getBillingAddress();
					} else {
						$address = $order->getShippingAddress();
					}
					
					$result[] = "
						ga('ecommerce:addTransaction', {
							id:          '".$order->getIncrementId()."', // Transaction ID
							affiliation: '".$this->jsQuoteEscape(Mage::app()->getStore()->getName())."', // Affiliation or store name
							revenue:     '".$order->getBaseGrandTotal()."', // Grand Total
							shipping:    '".$order->getBaseShippingAmount()."', // Shipping cost
							tax:         '".$order->getBaseTaxAmount()."', // Tax

						});
					";

					foreach ($order->getAllItems() as $item) {
					
						if ($item->getParentItemId()) {
							continue;
						}			

							$result[] = "
							ga('ecommerce:addItem', {

								id:       '".$order->getIncrementId()."', // Transaction ID.
								sku:      '".$this->jsQuoteEscape($item->getSku())."', // SKU/code.
								name:     '".$this->jsQuoteEscape($item->getName())."', // Product name.
								category: '', // Category or variation. there is no 'category' defined for the order item
								price:    '".$item->getBasePrice()."', // Unit price.
								quantity: '".$item->getQtyOrdered()."' // Quantity.

							});
						";

					}
					
				$result[] = "ga('ecommerce:send');";
			}
					
			
		
		return implode("\n", $result);
	}	
	
    /**
     * @deprecated after 1.4.1.1
     * @see self::_getOrdersTrackingCode()
     * @return string
     */
    public function getQuoteOrdersHtml()
    {
        return '';
    }
	
    /**
     * @deprecated after 1.4.1.1
     * self::_getOrdersTrackingCode()
     * @return string
     */
    public function getOrderHtml()
    {
        return '';
    }
	
    /**
     * Retrieve Google Account Identifier
     *
     * @return string
     */
    public function _getAccount()
    {
        if (!$this->hasData('account')) {
            $this->setAccount(Mage::getStoreConfig('google/analytics/account'));
        }
        return $this->_getPageTrackingCode($this->getData('account'));
    }	

    /**
     * Retrieve current page URL
     *
     * @return string
     */
    public function getPageName()
    {
        if (!$this->hasData('page_name')) {
            //$queryStr = '';
            //if ($this->getRequest() && $this->getRequest()->getQuery()) {
            //    $queryStr = '?' . http_build_query($this->getRequest()->getQuery());
            //}
            $this->setPageName(Mage::getSingleton('core/url')->escape($_SERVER['REQUEST_URI']));
        }
        return $this->getData('page_name');
    }
	
    /**
     * Retrieve Hostname defined in config
     *
     * @return string
     */
    public function getHostName()
    {
        $_hostName=Mage::getStoreConfig('google/analytics/hostname');
		
		if (empty($_hostName)) {
            return 'auto';
        }
		
		return $_hostName;
    }	

    /**
     * Render GA tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        // is analytics enabled ?
		if (!Mage::getStoreConfigFlag('google/analytics/active')) {
            return '';
        }

		$this->addText('
<!-- BEGIN GOOGLE UNIVERSAL ANALYTICS CODE -->
<script type="text/javascript">
//<![CDATA[
    (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');'. 
    $this->_getAccount().
    $this->_getOrdersTrackingCode(). '
//]]>
</script>
<!-- END GOOGLE UNIVERSAL ANALYTICS CODE -->
');
		
        return parent::_toHtml();
    }
	
    /**
     * Render IP anonymization code for page tracking javascript code - not used
     *
     * @return string
     */
    protected function _getAnonymizationCode()
    {
        return '';
    }	
}
