<?php

namespace IWD\Opc\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Framework\Event\ObserverInterface;
use IWD\Opc\Helper\Data as OpcHelper;
use Magento\Customer\Model\CustomerFactory;
use Psr\Log\LoggerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class QuoteSubmitSuccess implements ObserverInterface
{

    public $opcHelper;
    public $customerFactory;
    public $checkoutSession;
    public $historyFactory;
    public $logger;
    public $subscriber;
    public $orderRepository;

    public function __construct(
        OpcHelper $opcHelper,
        CustomerFactory $customerFactory,
        CheckoutSession $checkoutSession,
        HistoryFactory $historyFactory,
        LoggerInterface $logger,
        Subscriber $subscriber,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->opcHelper = $opcHelper;
        $this->customerFactory = $customerFactory;
        $this->checkoutSession = $checkoutSession;
        $this->historyFactory = $historyFactory;
        $this->logger = $logger;
        $this->subscriber = $subscriber;
        $this->orderRepository = $orderRepository;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->opcHelper->isEnable()) {
            /**
             * @var $order Order
             */

            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                return $this;
            }

            $this->assignOrderToCustomer($order);
            $this->saveComment($order);
            $this->saveSubscribe($order);
        }

        return $this;
    }

    private function saveSubscribe(Order $order)
    {
        if ($this->opcHelper->isShowSubscribe()) {
            $subscribe = $this->checkoutSession->getIwdOpcSubscribe();
            if ($subscribe) {
                try {
                    $this->subscriber->subscribe($order->getCustomerEmail());
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }

    private function saveComment(Order $order)
    {
        if ($this->opcHelper->isShowComment()) {
            $comment = $this->checkoutSession->getIwdOpcComment();
            if ($comment) {
                try {
                    $history = $this->historyFactory->create();
                    $history->setData('comment', $comment);
                    $history->setData('parent_id', $order->getId());
                    $history->setData('is_visible_on_front', 1);
                    $history->setData('is_customer_notified', 0);
                    $history->setData('entity_name', 'order');
                    $history->setData('status', $order->getStatus());
                    $history->save();
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }

    private function assignOrderToCustomer(Order $order)
    {
        if ($this->opcHelper->isAssignOrderToCustomer()) {
            try {
                if (!$order->getCustomerId()) {
                    $customerEmail = $order->getCustomerEmail();
                    $websiteId = $order->getStore()->getWebsiteId();
                    /** @var \Magento\Customer\Model\Customer $customer */
                    $customer = $this->customerFactory->create();
                    $customer->setWebsiteId($websiteId);
                    $customer->loadByEmail($customerEmail);
                    if ($customer->getId()) {
                        $order->setCustomerId($customer->getId());
                        $order->setCustomerGroupId($customer->getGroupId());
                        $order->setCustomerIsGuest(0);
                        $order->setCustomerFirstname($customer->getFirstname());
                        $order->setCustomerLastname($customer->getLastname());
                        if ($order->getShippingAddress()) {
                            $order->getShippingAddress()->setCustomerId($customer->getId());
                        }

                        $order->getBillingAddress()->setCustomerId($customer->getId());
                        $this->orderRepository->save($order);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
