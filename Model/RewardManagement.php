<?php

namespace IWD\Opc\Model;

use IWD\Opc\Api\RewardManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Reward\Helper\Data;

class RewardManagement implements RewardManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * Reward helper
     *
     * @var Data
     */
    public $rewardData;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Data $rewardData
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->rewardData = $rewardData;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($cartId)
    {
        if ($this->rewardData->isEnabledOnFront()) {
            $quote = $this->quoteRepository->get($cartId);
            $quote->setUseRewardPoints(false);
            $quote->collectTotals();
            $quote->save();
            return true;
        }

        return false;
    }
}
