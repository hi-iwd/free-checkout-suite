var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/quote': 'IWD_Opc/js/model/quote',
            'Magento_Checkout/js/checkout-data': 'IWD_Opc/js/checkout-data',
            'Magento_Checkout/js/model/checkout-data-resolver': 'IWD_Opc/js/model/checkout-data-resolver',
            'Magento_Checkout/js/view/summary/abstract-total': 'IWD_Opc/js/view/summary/abstract-total',

            'Magento_Checkout/js/model/shipping-rate-service': 'IWD_Opc/js/model/shipping-rate-service',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'IWD_Opc/js/model/shipping-save-processor/default',
            'Magento_Checkout/js/model/shipping-rates-validator': 'IWD_Opc/js/model/shipping-rates-validator',
            'Magento_Checkout/js/model/new-customer-address': 'IWD_Opc/js/model/new-customer-address',

            'Magento_Checkout/js/model/payment-service': 'IWD_Opc/js/model/payment-service',

            'Magento_CheckoutAgreements/js/model/agreement-validator': 'IWD_Opc/js/model/agreement-validator',
            'Magento_CheckoutAgreements/js/model/agreements-assigner': 'IWD_Opc/js/model/agreements-assigner',

            'Magento_Paypal/js/action/set-payment-method': 'IWD_Opc/js/action/paypal/set-payment-method',

            'Magento_Customer/js/action/login': 'IWD_Opc/js/action/login'
        }
    },
    paths: {
        'microplugin': 'IWD_Opc/js/libs/microplugin',
        'sifter': 'IWD_Opc/js/libs/sifter',
        'iwdOpcSelectize': 'IWD_Opc/js/libs/selectize',
        'iwdOpcHelper': 'IWD_Opc/js/helper'
    },
    shim: {
        'iwdOpcSelectize': {
            'deps': ['jquery', 'microplugin', 'sifter']
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/place-order': {
                'IWD_Opc/js/model/place-order-mixin': true
            },
            'Magento_Checkout/js/action/set-payment-information': {
                'IWD_Opc/js/model/set-payment-information-mixin': true
            }
        }
    }
};
