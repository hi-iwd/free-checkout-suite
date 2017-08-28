<?php

namespace IWD\Opc\Plugin;

use IWD\Opc\Helper\Data as OpcHelper;
use Magento\GiftCardAccount\Helper\Data as GiftCardAccountHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Store\Model\StoreManagerInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardAccountModel;
use Magento\Quote\Api\CartRepositoryInterface;

class GiftCardAccount
{
    public $opcHelper;
    public $giftCardAccountHelper;
    public $checkoutSession;
    public $storeManager;
    public $quoteRepository;

    public function __construct(
        OpcHelper $opcHelper,
        GiftCardAccountHelper $giftCardAccountHelper,
        Proxy $checkoutSession,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->opcHelper = $opcHelper;
        $this->giftCardAccountHelper = $giftCardAccountHelper;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
    }

    public function afterAddToCart(GiftCardAccountModel $subject, GiftCardAccountModel $result)
    {
        if ($this->opcHelper->isEnable()) {
            $quote = $this->checkoutSession->getQuote();
            if ($quote) {
                $website = $this->storeManager->getStore($quote->getStoreId())->getWebsite();
                if ($subject->isValid(true, true, $website)) {
                    $cards = $this->giftCardAccountHelper->getCards($quote);
                    if ($cards) {
                        foreach ($cards as $key => $card) {
                            $cards[$key]['oa'] = $card['a'];
                        }

                        $this->giftCardAccountHelper->setCards($quote, $cards);
                        $quote->collectTotals();
                        $this->quoteRepository->save($quote);
                    }
                }
            }
        }

        return $result;
    }
}
