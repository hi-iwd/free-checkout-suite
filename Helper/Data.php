<?php

namespace IWD\Opc\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Message\Session\Proxy as Session;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use IWD\Opc\Model\FlagFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\UrlInterface;

final class Data extends AbstractHelper
{

    const XML_PATH_ENABLE = 'iwd_opc/general/enable';

    const XML_PATH_TITLE = 'iwd_opc/extended/title';
    const XML_PATH_DISCOUNT_VISIBILITY = 'iwd_opc/extended/show_discount';
    const XML_PATH_COMMENT_VISIBILITY = 'iwd_opc/extended/show_comment';
    const XML_PATH_GIFT_MESSAGE_VISIBILITY = 'iwd_opc/extended/show_gift_message';
    const XML_PATH_LOGIN_BUTTON_VISIBILITY = 'iwd_opc/extended/show_login_button';
    const XML_PATH_SUBSCRIBE_VISIBILITY = 'iwd_opc/extended/show_subscribe';
    const XML_PATH_SUBSCRIBE_BY_DEFAULT = 'iwd_opc/extended/subscribe_by_default';
    const XML_PATH_ASSIGN_ORDER_TO_CUSTOMER = 'iwd_opc/extended/assign_order';
    const XML_PATH_RELOAD_SHIPPING_ON_DISCOUNT = 'iwd_opc/extended/reload_shipping_methods_on_discount';
    const XML_PATH_DEFAULT_SHIPPING_METHOD = 'iwd_opc/extended/default_shipping_method';
    const XML_PATH_DEFAULT_PAYMENT_METHOD = 'iwd_opc/extended/default_payment_method';
    const XML_PATH_SUCCESS_PAGE_VISIBILITY = 'iwd_opc/extended/show_success_page';
    const XML_PATH_PAYMENT_TITLE_TYPE = 'iwd_opc/extended/payment_title_type';

    const XML_PATH_RESTRICT_PAYMENT_ENABLE = 'iwd_opc/restrict_payment/enable';
    const XML_PATH_RESTRICT_PAYMENT_METHODS = 'iwd_opc/restrict_payment/methods';

    public $storeManager;
    public $resourceConfig;
    public $curlFactory;
    public $session;
    public $customerSession;
    public $flagFactory;
    public $response = null;
    public $jsonHelper;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        CurlFactory $curlFactory,
        Session $session,
        ConfigInterface $resourceConfig,
        FlagFactory $flagFactory,
        JsonHelper $jsonHelper
    ) {
        parent::__construct($context);
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->curlFactory = $curlFactory;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->flagFactory = $flagFactory;
        $this->jsonHelper = $jsonHelper;
    }

    public function isEnable()
    {
        $status = $this->scopeConfig->getValue(self::XML_PATH_ENABLE);
        return (bool)$status;
    }

    public function isCheckoutPage()
    {
        return $this->_getRequest()->getModuleName() === 'onepage'
            && $this->isEnable()
            && $this->isModuleOutputEnabled('IWD_Opc');
    }

    public function isCurrentlySecure()
    {
        return (bool)$this->storeManager->getStore()->isCurrentlySecure();
    }

    public function getTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TITLE);
    }

    public function getDefaultShippingMethod()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_SHIPPING_METHOD);
    }

    public function getDefaultPaymentMethod()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PAYMENT_METHOD);
    }

    public function getRestrictPaymentMethods()
    {
        $methods = $this->scopeConfig->getValue(self::XML_PATH_RESTRICT_PAYMENT_METHODS);
        return $methods ? $this->jsonHelper->jsonDecode($methods) : [];
    }

    public function isRestrictPaymentEnable()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_RESTRICT_PAYMENT_ENABLE);
    }

    public function isShowComment()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_COMMENT_VISIBILITY);
    }

    public function isShowDiscount()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_DISCOUNT_VISIBILITY);
    }

    public function isShowGiftMessage()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_GIFT_MESSAGE_VISIBILITY);
    }

    public function isShowLoginButton()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_LOGIN_BUTTON_VISIBILITY);
    }

    public function isShowSuccessPage()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_SUCCESS_PAGE_VISIBILITY);
    }

    public function isShowSubscribe()
    {
        $moduleStatus = $this->_moduleManager->isOutputEnabled('Magento_Newsletter');
        return $this->scopeConfig->getValue(self::XML_PATH_SUBSCRIBE_VISIBILITY)
            && $moduleStatus
            && !$this->customerSession->isLoggedIn();
    }

    public function isSubscribeByDefault()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_SUBSCRIBE_BY_DEFAULT);
    }

    public function isAssignOrderToCustomer()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ASSIGN_ORDER_TO_CUSTOMER);
    }

    public function isReloadShippingOnDiscount()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_RELOAD_SHIPPING_ON_DISCOUNT);
    }

    public function getPaymentTitleType()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_TITLE_TYPE);
    }
}
