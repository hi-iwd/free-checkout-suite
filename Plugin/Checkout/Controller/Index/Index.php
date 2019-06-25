<?php

namespace IWD\Opc\Plugin\Checkout\Controller\Index;

use IWD\Opc\Helper\Data as OpcHelper;
use Magento\Checkout\Controller\Index\Index as CheckoutActionIndex;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\UrlInterface;

class Index
{
    public $resultRedirectFactory;
    public $opcHelper;
    public $url;

    public function __construct(
        OpcHelper $opcHelper,
        ResponseHttp $response,
        UrlInterface $url
    ) {
        $this->opcHelper = $opcHelper;
        $this->response = $response;
        $this->url = $url;
    }

    public function beforeExecute(CheckoutActionIndex $subject)
    {
        if ($this->opcHelper->isEnable()) {
            $url = $this->url->getUrl('onepage');
            $this->response->setRedirect($url);
        }
    }
}
