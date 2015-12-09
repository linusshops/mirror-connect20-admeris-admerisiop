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

class Admeris_AdmerisIop_ProcessingController extends Mage_Core_Controller_Front_Action
{
    protected $_redirectBlockType = 'admerisiop/processing';
    protected $_successBlockType = 'admerisiop/success';
    protected $_failureBlockType = 'admerisiop/failure';
    
    protected $_sendNewOrderEmail = true;
    
    protected $_order = NULL;
    protected $_paymentInst = NULL;
	
    protected function _expireAjax()
    {
        if (!$this->getCheckout()->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * when customer select Admeris Interac Online payment method
     */
    public function redirectAction()
    {
        
        $session = $this->getCheckout();
        $session->setAdmerisiopQuoteId($session->getQuoteId());
        $session->setAdmerisiopRealOrderId($session->getLastRealOrderId());

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
		$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_HOLDED, Mage::helper('admerisiop')->__('Customer was redirected to Admeris.'));
        $order->save();

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_redirectBlockType)
                ->setOrder($order)
                ->toHtml()
        );

        $session->unsQuoteId();
         
    }
    
    
    
    /**
     * Admeris returns POST variables to this action
     */
    public function responseAction()
    {
    	try {
            $response = $this->_checkReturnedPost();
    		
            $array = array('iop',$response['invoice'], $response['amount'], $response['date'], $response['issname'], $response['issconf']);
            $comma_separated = implode(",", $array);
    		// save transaction ID and approval info
    		$this->_paymentInst
    			->setTransactionId($response['transactionId'])
                ->setCcApproval($comma_separated);

            if ($this->_order->canInvoice()) {
            	$invoice = $this->_order->prepareInvoice();
            	
                $invoice->register()->capture(); 
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }
            $this->_order->addStatusToHistory($this->_paymentInst->getConfigData('order_status'), Mage::helper('admerisiop')->__($this->_paymentInst->getConfigData('response_type').':Customer returned successfully'));
            $this->_order->save();

	        $this->getResponse()->setBody(
	            $this->getLayout()
	                ->createBlock($this->_successBlockType)
	                ->setOrder($this->_order)
	                ->toHtml()
	        );
                        
    	} catch (Exception $e) {
            Mage::log($e->getMessage());
    		$this->getResponse()->setBody(
	            $this->getLayout()
	                ->createBlock($this->_failureBlockType)
	                ->setOrder($this->_order)
	                ->toHtml()
	        );
    	}

    }

    
    
    /**
     * Admeris return action
     */
    protected function successAction()
    {
        $session = $this->getCheckout();

        $session->unsAdmerisiopRealOrderId();
        $session->setQuoteId($session->getAdmerisiopQuoteId(true));
        $session->getQuote()->setIsActive(false)->save();

        $order = Mage::getModel('sales/order');
        $order->load($this->getCheckout()->getLastOrderId());
        if($order->getId() && $this->_sendNewOrderEmail)
            $order->sendNewOrderEmail();

		$this->_redirect('checkout/onepage/success');
    }

    
    
    /**
     * Checking POST variables.
     * Creating invoice if payment was successfull or cancel order if payment was declined
     */
    protected function _checkReturnedPost()
    {
        // check response type
        if (!$this->getRequest()->isPost())
        {
        	throw new Exception('Wrong response type.');
        }

        // get response variables
        $response = $this->getRequest()->getPost();
        
        // check transaction status
        if ($response['status'] == 'failed')
        {
            $_SESSION['invoice'] = $response['invoice'];
            $_SESSION['date'] = $response['date'];
            throw new Exception('unsuccessful transaction');
        }

        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($response['invoice']);
        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();
               	
        // check transaction amount
        $amount = number_format($this->_order->getBaseGrandTotal(),2,'.',''); 
        if ($amount != $response['amount'])
        {
        	throw new Exception('Transaction amount doesn\'t match.');
        }
             
        return $response;
    }
}