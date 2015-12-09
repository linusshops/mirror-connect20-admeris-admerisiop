<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Admeris
 * @package    Admeris_AdmerisIop
 * @copyright  Copyright (c) 2009 Admeris Payment Systems (http://www.admeris.com/)
 */

class Admeris_AdmerisIop_Model_iop extends Mage_Payment_Model_Method_Abstract
{  
	protected $_code = 'admerisiop';

    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;

    protected $_dUrl = 'https://test.admeris.com/store/checkout/payment.jsp';
    protected $_pUrl	= 'https://ec1.admeris.com/checkout/payment.jsp';

    protected $_formBlockType = 'admerisiop/form';
    protected $_infoBlockType = 'admerisiop/info';
    protected $_failureBlockType = 'admerisiop/failure';
    
    protected $_order;
 
    
    
    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = Mage::getModel('sales/order')
                            ->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
        }
        return $this->_order;
    }
   
    
    
    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('admerisiop/processing/redirect');
    }
    
    
    
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setLastTransId($this->getTransactionId())
            ->setCcApproval($this->getCcApproval());

        return $this;
    }
    
    
    
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setLastTransId($this->getTransactionId())
            ->setCcApproval($this->getCcApproval());
        return $this;
    }

     
    
    public function getUrl()
    {
        if ($this->getConfigData('payment_environment') == 'P')
        {
            return $this->_pUrl;
    	} else{
            return $this->_dUrl;
        }
    }
    
    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {        
		$billing = $this->getOrder()->getBillingAddress();
        $shipping = $this->getOrder()->getShippingAddress();
		
        $params = array(
                    'merchantId' => $this->getConfigData('merchant_id'),
                    'storeId' =>$this->getConfigData('store_id'),
                    'total' =>number_format($this->getOrder()->getGrandTotal(),2,'.',''),
                    'invoice' => $this->getOrder()->getRealOrderId(),
                        
                    'bContactName'  =>  Mage::helper('core')->removeAccents($billing->getFirstname().' '.$billing->getLastname()),
                    'sContactName'  =>  Mage::helper('core')->removeAccents($shipping->getFirstname().' '.$shipping->getLastname()),
                        
                        // billing address
                    'bAddress1' =>  $billing->getStreet1(),
                    'bAddress2' => $billing->getStreet2(),
                    'bCity' => $shipping->getCity(),
                    'bProvince' => $billing->getRegion(),
                    'bCountry' => $billing->getCountry(),
                    'bPostal' => $billing->getPostcode(),
                        
                        // shipping address 
                    'sAddress1' => $shipping->getStreet1(),
                    'sAddress2' => $shipping->getStreet2(),
                    'sCity' => $shipping->getCity(),
                    'sProvince' => $shipping->getRegion(),
                    'sCountry' => $shipping->getCountry(),
                    'sPostal' => $shipping->getPostcode(),
                        
                    'oEmail' => $this->getOrder()->getCustomerEmail(),
                    'successUrl' =>  Mage::getUrl('admerisiop/processing/response', array('_secure'=>true)),
                    'failureUrl' =>  Mage::getUrl('admerisiop/processing/response', array('_secure'=>true)));
                        
        return $params;
    }
}