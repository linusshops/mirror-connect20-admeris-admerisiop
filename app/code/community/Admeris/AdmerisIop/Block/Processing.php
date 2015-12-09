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

class Admeris_AdmerisIop_Block_Processing extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $payment = $this->getOrder()->getPayment()->getMethodInstance();

        $form = new Varien_Data_Form();
        $form->setAction($payment->getUrl())
            ->setId('admerisiop_checkout')
            ->setName('admerisiop_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($payment->getFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to Admeris in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("admerisiop_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}