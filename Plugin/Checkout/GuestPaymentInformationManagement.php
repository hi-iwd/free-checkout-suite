<?php

namespace IWD\Opc\Plugin\Checkout;

use IWD\Opc\Helper\Data as OpcHelper;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

class GuestPaymentInformationManagement
{
    public $opcHelper;
    public $checkoutSession;
    public $logger;

    public function __construct(
        OpcHelper $opcHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->opcHelper = $opcHelper;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundSavePaymentInformation(
        $subject,
        callable $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $result = $proceed($cartId, $email, $paymentMethod, $billingAddress);
        if ($this->opcHelper->isEnable()) {
            $this->saveCommentToSession($paymentMethod);
            $this->saveSubscribeToSession($paymentMethod);
        }

        return $result;
    }

    public function saveCommentToSession(
        PaymentInterface $paymentMethod
    ) {
        if ($this->opcHelper->isShowComment()) {
            $comment = $paymentMethod->getExtensionAttributes() === null
                ? ''
                : trim($paymentMethod->getExtensionAttributes()->getComment());
            $this->checkoutSession->setIwdOpcComment($comment);
        }
    }

    public function saveSubscribeToSession(
        PaymentInterface $paymentMethod
    ) {
        if ($this->opcHelper->isShowSubscribe()) {
            $subscribe = $paymentMethod->getExtensionAttributes() === null
                ? false
                : $paymentMethod->getExtensionAttributes()->getSubscribe();
            $this->checkoutSession->setIwdOpcSubscribe($subscribe);
        }
    }
}
