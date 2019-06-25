<?php

namespace IWD\Opc\Model;

use IWD\Opc\Api\RewardManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
//use Magento\Reward\Helper\Data;
use Magento\Framework\ObjectManagerInterface;

class RewardManagement implements RewardManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

//    /**
//     * Reward helper
//     *
//     * @var Data
//     */
//    public $rewardData;
    public $objectManager;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ObjectManagerInterface $objectManager
//        Data $rewardData
    ) {
        $this->quoteRepository = $quoteRepository;
//        $this->rewardData = $rewardData;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($cartId)
    {
        /**
         * @var $rewardHelper \Magento\Reward\Helper\Data
         */
        $rewardHelper = $this->objectManager->create('\Magento\Reward\Helper\Data');
        if ($rewardHelper->isEnabledOnFront()) {
            $quote = $this->quoteRepository->get($cartId);
            $quote->setUseRewardPoints(false);
            $quote->collectTotals();
            $quote->save();
            return true;
        }

        return false;
    }
}
