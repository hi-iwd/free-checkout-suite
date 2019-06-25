<?php

namespace IWD\Opc\Plugin;

use IWD\Opc\Helper\Data as OpcHelper;
//use Magento\GiftCardAccount\Helper\Data as GiftCardAccountHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class GiftCardAccount
{
    public $opcHelper;
//    public $giftCardAccountHelper;
    public $objectManager;
    public $checkoutSession;
    public $storeManager;
    public $quoteRepository;

    public function __construct(
        OpcHelper $opcHelper,
//        GiftCardAccountHelper $giftCardAccountHelper,
        ObjectManagerInterface $objectManager,
        Proxy $checkoutSession,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->opcHelper = $opcHelper;
//        $this->giftCardAccountHelper = $giftCardAccountHelper;
        $this->objectManager = $objectManager;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param $subject \Magento\GiftCardAccount\Model\Giftcardaccount
     * @param $result \Magento\GiftCardAccount\Model\Giftcardaccount
     * @return \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    public function afterAddToCart($subject, $result)
    {
        if ($this->opcHelper->isEnable()) {
            $quote = $this->checkoutSession->getQuote();
            if ($quote) {
                $website = $this->storeManager->getStore($quote->getStoreId())->getWebsite();
                if ($subject->isValid(true, true, $website)) {
                    /**
                     * @var $giftCardAccountHelper \Magento\GiftCardAccount\Helper\Data
                     */
                    $giftCardAccountHelper = $this->objectManager->create('\Magento\GiftCardAccount\Helper\Data');
                    $cards = $giftCardAccountHelper->getCards($quote);
                    if ($cards) {
                        foreach ($cards as $key => $card) {
                            $cards[$key]['oa'] = $card['a'];
                        }

                        $giftCardAccountHelper->setCards($quote, $cards);
                        $quote->collectTotals();
                        $this->quoteRepository->save($quote);
                    }
                }
            }
        }

        return $result;
    }
}
