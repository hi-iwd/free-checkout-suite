<?php

namespace IWD\Opc\Controller\Index;

use IWD\Opc\Controller\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\ResultFactory; 
use Magento\Framework\App\Action\Context;

class Ajaxr extends \Magento\Framework\App\Action\Action
{
	
    public function __construct(
	\Magento\Framework\App\Action\Context $context	
      
)
  {
	    return parent::__construct($context);
  }
    
    public function execute()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		  $orderId =  $this->getRequest()->getPost('id', false);
	    if($orderId!=''){
		$orderi = $objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId)->getOrderId(); 
		$order = $objectManager->get('Magento\Sales\Model\Order')->load($orderi);
		$quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($order->getQuoteId());
		 $data = $this->getRequest()->getPost('q', false); 
		 $ignor=array('firstname','lastname','street','city','country_id','region_id','postcode','region','telephone','company');
		 
		foreach(json_decode($data) as $key=>$val){
			if (!in_array($key, $ignor)){
		$order->getBillingAddress()->setData($key, $val);
		$order->getShippingAddress()->setData($key, $val);
		$quote->getBillingAddress()->setData($key, $val);
		$quote->getShippingAddress()->setData($key, $val);
			}
		}
		$order->getBillingAddress()->save();
		$order->getShippingAddress()->save();
		$quote->getBillingAddress()->save();
		$quote->getShippingAddress()->save();    
    $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
    $resultJson->setData($data,false); 
    return $resultJson; 
	}
    }
}
