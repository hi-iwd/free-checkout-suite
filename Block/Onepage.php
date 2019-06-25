<?php

namespace IWD\Opc\Block;

use Magento\Checkout\Block\Onepage as CheckoutOnepage;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\CompositeConfigProvider;

class Onepage extends CheckoutOnepage
{

    public $checkoutSession;
    public $quote = null;

    public function __construct(
        Context $context,
        FormKey $formKey,
        CompositeConfigProvider $configProvider,
        CheckoutSession $checkoutSession,
        array $layoutProcessors = [],
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data);
    }

    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }
}
