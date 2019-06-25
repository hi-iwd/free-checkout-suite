<?php

namespace IWD\Opc\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Ui\Component\Form\AttributeMapper;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use IWD\Opc\Helper\Data as OpcHelper;

class UpdateLayoutProcessor implements LayoutProcessorInterface
{
    public $jsLayout;

    public $attributeMetadataDataProvider;
    public $attributeMapper;
    public $merger;
    public $checkoutSession;
    public $quote = null;
    public $opcHelper;

    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        CheckoutSession $checkoutSession,
        OpcHelper $opcHelper
    )
    {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->checkoutSession = $checkoutSession;
        $this->opcHelper = $opcHelper;
    }

    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        $this->jsLayout = $jsLayout;
        if ($this->opcHelper->isCheckoutPage()) {
            $this->updateOnePage();
            $this->updateShipping();
            $this->processAddressFields();
            $this->updatePayment();
            $this->updateLoginButton();
            $this->updateTotals();
        }

        return $this->jsLayout;
    }

    public function processAddressFields()
    {
        $shippingFields = $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
        $shippingFields = $this->createPlaceholders($shippingFields);
        $shippingFields = $this->updateUiComponents($shippingFields);
        $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $shippingFields;
        $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['customer-email']['placeholder'] = __('Email Address') . ' *';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['customer-email']['passwordPlaceholder'] = __('Password');

        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['customer-email']['placeholder'] = __('Email Address') . ' *';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['customer-email']['passwordPlaceholder'] = __('Password');

        if (isset($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form'])) {
            $billingFields = $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']
            ['form-fields']['children'];
            $billingFields = $this->addEeCustomAttributes($billingFields);
            $billingFields = $this->createPlaceholders($billingFields);
            $billingFields = $this->updateUiComponents($billingFields);
            $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']
            ['form-fields']['children'] = $billingFields;
        } else {
            foreach ($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                     ['children']['payment']['children']['payments-list']['children'] as $paymentCode => $paymentMethod) {
                if (isset($paymentMethod['children']['form-fields']['children'])) {
                    $billingFields = $paymentMethod['children']['form-fields']['children'];
                    $billingFields = $this->createPlaceholders($billingFields);
                    $billingFields = $this->updateUiComponents($billingFields);
                    $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['payments-list']['children'][$paymentCode]['children']
                    ['form-fields']['children'] = $billingFields;
                }
            }
        }
    }

    public function addEeCustomAttributes($fields)
    {
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );
        $addressElements = [];
        foreach ($attributes as $attribute) {
            if (!$attribute->getIsUserDefined()) {
                continue;
            }

            $addressElements[$attribute->getAttributeCode()] = $this->attributeMapper->map($attribute);
        }

        if ($addressElements) {
            $fields = $this->merger->merge(
                $addressElements,
                'checkoutProvider',
                'billingAddressshared.custom_attributes',
                $fields
            );
        }

        return $fields;
    }

    public function createPlaceholders($fields)
    {
        foreach ($fields as $key => $data) {
            if ((!isset($data['placeholder']) || !$data['placeholder'])) {
                $placeholder = isset($data['label']) && $data['label'] ?
                    $data['label'] : $this->getPlaceholderForField($key);

                if ($placeholder) {
                    if (isset($data['type']) && $data['type'] === 'group'
                        && isset($data['children']) && !empty($data['children'])
                    ) {
                        foreach ($data['children'] as $childrenKey => $childrenData) {
                            $is_required = false;
                            if (!isset($data['placeholder']) || !$data['placeholder']) {
                                if (isset($fields[$key]['children'][$childrenKey]['additionalClasses']) &&
                                    $fields[$key]['children'][$childrenKey]['additionalClasses'] === true
                                ) {
                                    $fields[$key]['children'][$childrenKey]['additionalClasses'] = 'additional';
                                }

                                if (isset($fields[$key]['children'][$childrenKey]['validation']['required-entry'])
                                    && $fields[$key]['children'][$childrenKey]['validation']['required-entry']
                                ) {
                                    if (isset($fields[$key]['children'][$childrenKey]['options'][0])) {
                                        $fields[$key]['children'][$childrenKey]['options'][0]['label'] .= ' *';
                                    } else {
                                        $is_required = true;
                                    }
                                }

                                $fields[$key]['children'][$childrenKey]['placeholder'] = $placeholder . ($is_required ? ' *' : '');
                            }
                        }
                    } else {
                        if (isset($fields[$key]['validation']['required-entry'])
                            && $fields[$key]['validation']['required-entry']
                        ) {
                            if (isset($fields[$key]['options'][0])) {
                                $fields[$key]['options'][0]['label'] .= ' *';
                            } else {
                                $placeholder .= ' *';
                            }
                        }

                        $fields[$key]['placeholder'] = $placeholder;
                    }
                }
            }
        }

        return $fields;
    }

    public function getPlaceholderForField($key)
    {
        $placeholder = '';
        $arrFields = [
            'fax' => __('Fax'),
        ];
        if (isset($arrFields[$key])) {
            $placeholder = $arrFields[$key];
        }

        return $placeholder;
    }

    public function updateUiComponents($fields)
    {
        foreach ($fields as $key => $data) {
            if (isset($data['type']) && $data['type'] === 'group'
                && isset($data['children']) && !empty($data['children'])
            ) {
                foreach ($data['children'] as $childrenKey => $childrenData) {
                    if (isset($childrenData['component'])) {
                        $fields[$key]['children'][$childrenKey]['component'] =
                            $this->getReplacedUiComponent($childrenData['component']);
                        if (isset($childrenData['config']['elementTmpl'])) {
                            $fields[$key]['children'][$childrenKey]['config']['elementTmpl'] =
                                $this->getReplacedUiTemplate($childrenData['config']['elementTmpl']);
                        }
                    }
                }
            } else {
                if (isset($data['component'])) {
                    $fields[$key]['component'] = $this->getReplacedUiComponent($data['component']);
                    if (isset($data['config']['elementTmpl'])) {
                        $fields[$key]['config']['elementTmpl'] =
                            $this->getReplacedUiTemplate($data['config']['elementTmpl']);
                    }
                }
            }
        }

        return $fields;
    }

    public function getReplacedUiComponent($component)
    {
        $arrComponents = [
            'Magento_Ui/js/form/element/region' => 'IWD_Opc/js/form/element/region',
            'Magento_Ui/js/form/element/select' => 'IWD_Opc/js/form/element/select',
            'Magento_Ui/js/form/element/textarea' => 'IWD_Opc/js/form/element/textarea',
            'Magento_Ui/js/form/element/multiselect' => 'IWD_Opc/js/form/element/multiselect',
            'Magento_Ui/js/form/element/post-code' => 'IWD_Opc/js/form/element/post-code',
        ];

        if (isset($arrComponents[$component])) {
            $component = $arrComponents[$component];
        }

        return $component;
    }

    public function getReplacedUiTemplate($template)
    {
        $arrTemplates = [
            'ui/form/element/select' => 'IWD_Opc/form/element/select',
            'ui/form/element/textarea' => 'IWD_Opc/form/element/textarea',
            'ui/form/element/multiselect' => 'IWD_Opc/form/element/multiselect',
        ];

        if (isset($arrTemplates[$template])) {
            $template = $arrTemplates[$template];
        }

        return $template;
    }

    public function updateShipping()
    {
        $shipping = [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'component' => 'IWD_Opc/js/view/shipping',
                                            'children' => [
                                                'customer-email' => [
                                                    'component' => 'IWD_Opc/js/view/form/element/email',
                                                    'children' => [
                                                        'errors' => [
                                                            'component' => 'IWD_Opc/js/view/form/element/email/errors',
                                                            'displayArea' => 'errors'
                                                        ],
                                                        'additional-login-form-fields' => [
                                                            'children' => [
                                                                'captcha' => [
                                                                    'config' => [
                                                                        'template' => 'IWD_Opc/captcha'
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                'before-shipping-method-form' => [
                                                    'children' => [
                                                        'shipping_policy' => [
                                                            'component' => 'IWD_Opc/js/view/shipping/shipping-policy',
                                                            'config' => [
                                                                'template' => 'IWD_Opc/shipping/shipping-policy'
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                'gift-message' => [
                                                    'displayArea' => 'gift-message',
                                                    'component' => 'IWD_Opc/js/view/gift-message',
                                                    'componentDisabled' => $this->getQuote()->isVirtual(),
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->setComponent($shipping);
    }

    public function updatePayment()
    {
        $payment = [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'billing-step' => [
                                    'children' => [
                                        'payment' => [
                                            'component' => 'IWD_Opc/js/view/payment',
                                            'children' => [
                                                'customer-email' => [
                                                    'component' => 'IWD_Opc/js/view/form/element/email',
                                                    'children' => [
                                                        'errors' => [
                                                            'component' => 'IWD_Opc/js/view/form/element/email/errors',
                                                            'displayArea' => 'errors'
                                                        ],
                                                        'additional-login-form-fields' => [
                                                            'children' => [
                                                                'captcha' => [
                                                                    'config' => [
                                                                        'template' => 'IWD_Opc/captcha'
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                'payments-list' => [
                                                    'component' => 'IWD_Opc/js/view/payment/list',
                                                    'children' => [
                                                        'before-place-order' => [
                                                            'children' => [
                                                                'agreements' => [
                                                                    'component' => 'IWD_Opc/js/view/checkout-agreements'
                                                                ],
                                                                'gift-card-information' => [
                                                                    'componentDisabled' => true,
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                'additional-payment-validators' => [
                                                    'children' => [
                                                        'shipping-information-validator' => [
                                                            'component' => 'IWD_Opc/js/view/shipping/shipping-information-validation'
                                                        ],
                                                        'payment-method-validator' => [
                                                            'component' => 'IWD_Opc/js/view/payment/payment-method-validation'
                                                        ],
                                                        'billing-address-validator' => [
                                                            'component' => 'IWD_Opc/js/view/billing/address-validation'
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->setComponent($payment);
        $afterMethods = $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['afterMethods']['children'];
        if (isset($afterMethods['discount'])) {
            $afterMethods['discount']['component'] = 'IWD_Opc/js/view/payment/discount';
            $afterMethods['discount']['children']['errors']['component'] = 'IWD_Opc/js/view/payment/discount/errors';
        }

        if (isset($afterMethods['storeCredit'])) {
            $afterMethods['storeCredit']['component'] = 'IWD_Opc/js/view/payment/customer-balance';
        }

        if (isset($afterMethods['giftCardAccount'])) {
            $afterMethods['giftCardAccount']['component'] = 'IWD_Opc/js/view/payment/gift-card-account';
            $afterMethods['giftCardAccount']['children']['errors']['component'] = 'IWD_Opc/js/view/payment/gift-card/errors';
        }

        if (isset($afterMethods['reward'])) {
            $afterMethods['reward']['component'] = 'IWD_Opc/js/view/payment/reward';
        }

        if (isset($afterMethods['billing-address-form'])) {
            $afterMethods['billing-address-form']['component'] = 'IWD_Opc/js/view/billing-address';
            $afterMethods['billing-address-form']['displayArea'] = 'billing-address-form';
            if ($this->getQuote()->isVirtual()) {
                $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step-virtual'] =
                    [
                        'component' => 'IWD_Opc/js/view/billing-step-virtual',
                        'sortOrder' => '1',
                        'children' => [
                            'billing-address-form' => $afterMethods['billing-address-form']
                        ]
                    ];
            } else {
                $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['billing-address-form'] = $afterMethods['billing-address-form'];
            }

            unset($afterMethods['billing-address-form']);
        } else {
            if ($this->getQuote()->isVirtual()) {
                foreach ($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                         ['children']['payment']['children']['payments-list']['children'] as $formCode => $billingForm) {
                    if ($billingForm['component'] === 'Magento_Checkout/js/view/billing-address') {
                        if (!isset($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step-virtual'])) {
                            $billingForm['displayArea'] = 'billing-address-form';
                            $billingForm['dataScopePrefix'] = 'billingAddressshared';
                            $billingForm['component'] = 'IWD_Opc/js/view/billing-address';
                            foreach ($billingForm['children']['form-fields']['children'] as $fieldCode => $fieldConfig) {
                                $customScope = null;
                                $customEntry = null;
                                $dataScope = null;
                                $code = '';
                                if (isset($fieldConfig['config']['customScope'])) {
                                    $code = str_replace('billingAddress', '', $fieldConfig['config']['customScope']);
                                }

                                if (!$code && isset($fieldConfig['config']['customEntry'])) {
                                    $code = str_replace('billingAddress', '', $fieldConfig['config']['customEntry']);
                                    $code = str_replace('.' . $fieldCode, '', $code);
                                }

                                if (!$code && isset($fieldConfig['dataScope'])) {
                                    $code = str_replace('billingAddress', '', $fieldConfig['dataScope']);
                                    $code = str_replace('.' . $fieldCode, '', $code);
                                }

                                if (!$code) {
                                    continue;
                                }

                                if (isset($fieldConfig['config']['customScope'])) {
                                    $customScope = $fieldConfig['config']['customScope'];
                                    if ($customScope) {
                                        $fieldConfig['config']['customScope'] = str_replace($code, 'shared', $customScope);
                                    }
                                }

                                if (isset($fieldConfig['config']['customEntry'])) {
                                    $customEntry = $fieldConfig['config']['customEntry'];
                                    if ($customEntry) {
                                        $fieldConfig['config']['customEntry'] = str_replace($code, 'shared', $customEntry);
                                    }
                                }

                                if (isset($fieldConfig['dataScope'])) {
                                    $dataScope = $fieldConfig['dataScope'];
                                    if ($dataScope) {
                                        $fieldConfig['dataScope'] = str_replace($code, 'shared', $dataScope);
                                    }
                                }

                                if (isset($fieldConfig['type']) && $fieldConfig['type'] === 'group') {
                                    foreach ($fieldConfig['children'] as $childrenKey => $childrenData) {
                                        $customScope = null;
                                        $customEntry = null;
                                        $dataScope = null;
                                        if (isset($childrenData['config']['customScope'])) {
                                            $customScope = $childrenData['config']['customScope'];
                                            if ($customScope) {
                                                $childrenData['config']['customScope'] = str_replace($code, 'shared', $customScope);
                                            }
                                        }

                                        if (isset($childrenData['config']['customEntry'])) {
                                            $customEntry = $childrenData['config']['customEntry'];
                                            if ($customEntry) {
                                                $childrenData['config']['customEntry'] = str_replace($code, 'shared', $customEntry);
                                            }
                                        }

                                        if (isset($childrenData['dataScope'])) {
                                            $dataScope = $childrenData['dataScope'];
                                            if ($dataScope) {
                                                $childrenData['dataScope'] = str_replace($code, 'shared', $dataScope);
                                            }
                                        }

                                        $fieldConfig['children'][$childrenKey] = $childrenData;
                                    }
                                }

                                $billingForm['children']['form-fields']['children'][$fieldCode] = $fieldConfig;
                            }

                            $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step-virtual'] =
                                [
                                    'component' => 'IWD_Opc/js/view/billing-step-virtual',
                                    'sortOrder' => '1',
                                    'children' => [
                                        'billing-address-form' => $billingForm
                                    ]
                                ];
                        }

                        unset($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                            ['children']['payment']['children']['payments-list']['children'][$formCode]);
                    }
                }
            } else {
                foreach ($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                         ['children']['payment']['children']['payments-list']['children'] as $paymentCode => $paymentMethod) {
                    if (isset($paymentMethod['children']['form-fields']['children'])) {
                        $paymentMethod['component'] = 'IWD_Opc/js/view/billing-address';
                        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                        ['children']['payment']['children']['payments-list']['children'][$paymentCode] = $paymentMethod;
                    }
                }
            }
        }

        if ($this->getQuote()->isVirtual()) {
            $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step-virtual']['children']
            ['customer-email'] = $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['customer-email'];
        }

        unset($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['customer-email']);

        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['afterMethods']['children'] = $afterMethods;

        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['before-place-order'] = $this->jsLayout['components']['checkout']
        ['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']
        ['children']['before-place-order'];

        unset($this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['before-place-order']);
    }

    public function updateOnePage()
    {
        $onePage = [
            'components' => [
                'checkout' => [
                    'config' => [
                        'template' => 'IWD_Opc/onepage'
                    ],
                    'children' => [
                        'errors' => [
                            'component' => 'IWD_Opc/js/view/errors',
                            'displayArea' => 'errors'
                        ],
                        'progressBar' => [
                            'componentDisabled' => true,
                        ],
                        'estimation' => [
                            'componentDisabled' => true,
                        ],
                        'authentication' => [
                            'componentDisabled' => true,
                        ],
                    ]
                ]
            ]
        ];
        $this->setComponent($onePage);
    }

    public function updateLoginButton()
    {
        $this->jsLayout['components']['checkout']['children']['login-button'] = [
            'component' => 'IWD_Opc/js/view/login-button',
            'displayArea' => 'login-button',
        ];
    }

    public function updateTotals()
    {
        $sidebar = [
            'components' => [
                'checkout' => [
                    'children' => [
                        'sidebar' => [
                            'component' => 'uiComponent',
                            'config' => [
                                'template' => 'IWD_Opc/sidebar'
                            ],
                            'children' => [
                                'summary' => [
                                    'component' => 'IWD_Opc/js/view/summary',
                                    'config' => [
                                        'template' => 'IWD_Opc/summary'
                                    ],
                                    'children' => [
                                        'totals' => [
                                            'config' => [
                                                'template' => 'IWD_Opc/summary/totals'
                                            ],
                                            'children' => [
                                                'subtotal' => [
                                                    'component' => 'Magento_Tax/js/view/checkout/summary/subtotal',
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/subtotal'
                                                    ],
                                                ],
                                                'shipping' => [
                                                    'component' => 'Magento_Tax/js/view/checkout/summary/shipping',
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/shipping'
                                                    ],
                                                ],
                                                'grand-total' => [
                                                    'component' => 'Magento_Tax/js/view/checkout/summary/grand-total',
                                                    'displayArea' => 'grand-total',
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/grand-total'
                                                    ],
                                                ],
                                                'discount' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/discount'
                                                    ],
                                                ],
                                                'tax' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/tax'
                                                    ],
                                                ],
                                                'weee' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/weee'
                                                    ],
                                                ],
                                                'customerbalance' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/customer-balance'
                                                    ],
                                                ],
                                                'storecredit' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/customer-balance'
                                                    ],
                                                ],
                                                'giftCardAccount' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/totals/gift-card-account'
                                                    ],
                                                ],
                                                'before_grandtotal' => [
                                                    'children' => [
                                                        'gift-wrapping-order-level' => [
                                                            'template' => 'IWD_Opc/summary/totals/gift-wrapping'
                                                        ],
                                                        'gift-wrapping-item-level' => [
                                                            'template' => 'IWD_Opc/summary/totals/gift-wrapping'
                                                        ],
                                                        'printed-card' => [
                                                            'template' => 'IWD_Opc/summary/totals/gift-wrapping'
                                                        ],
                                                        'reward' => [
                                                            'template' => 'IWD_Opc/summary/totals/reward'
                                                        ],
                                                    ]
                                                ]
                                            ]
                                        ],
                                        'cart_items' => [
                                            'displayArea' => 'cart_items',
                                            'config' => [
                                                'template' => 'IWD_Opc/summary/cart-items'
                                            ],
                                            'children' => [
                                                'details' => [
                                                    'config' => [
                                                        'template' => 'IWD_Opc/summary/item/details'
                                                    ],
                                                    'children' => [
                                                        'subtotal' => [
                                                            'component' => 'Magento_Tax/js/view/checkout/summary/item/details/subtotal',
                                                            'config' => [
                                                                'template' => 'IWD_Opc/summary/item/details/subtotal'
                                                            ],
                                                            'children' => [
                                                                'weee_row_incl_tax' => [
                                                                    'config' => [
                                                                        'template' => 'IWD_Opc/summary/item/details/price/row_incl_tax'
                                                                    ],
                                                                ],
                                                                'weee_row_excl_tax' => [
                                                                    'config' => [
                                                                        'template' => 'IWD_Opc/summary/item/details/price/row_excl_tax'
                                                                    ],
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ],
                                        'itemsBefore' => [
                                            'displayArea' => 'itemsBefore'
                                        ],
                                        'itemsAfter' => [
                                            'displayArea' => 'itemsAfter'
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']
        ['grand-total'] = $this->jsLayout['components']['checkout']['children']['sidebar']['children']
        ['summary']['children']['totals']['children']['grand-total'];
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']
        ['grand-total']['config']['template'] = 'IWD_Opc/summary/grand-total';
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']
        ['grand-total']['displayArea'] = 'grand-total-head';
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']
        ['grand-total']['component'] = 'Magento_Tax/js/view/checkout/summary/grand-total';
        $this->setComponent($sidebar);
    }

    public function setComponent($component)
    {
        $this->jsLayout = $this->arrayMergeRecursiveEx($this->jsLayout, $component);
        return $this->jsLayout;
    }

    private function arrayMergeRecursiveEx(array & $array1, array & $array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveEx($merged[$key], $value);
            } elseif (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
