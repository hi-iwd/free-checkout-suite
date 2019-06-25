define([
    'Magento_Braintree/js/view/payment/method-renderer/hosted-fields',
    'mage/translate'
], function (Component, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_Opc/payment/methods-renderers/braintree/form',
            isCurrentlySecure: window.checkoutConfig.iwdOpcSettings.isCurrentlySecure
        },
        getHostedFields: function () {
            var self = this;
            var fields = self._super();
            if (fields.cvv) {
                fields.cvv.placeholder = $t('CVV') + ' *';
            }

            fields.number.placeholder = $t('Credit Card Number') + ' *';
            fields.expirationMonth.placeholder = $t('MM') + ' *';
            fields.expirationYear.placeholder = $t('YY') + ' *';
            fields.styles = {
                "input": {
                    "font-size": "14px",
                    "color": "rgb(52,52,52)",
                    "font-family": "'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif"
                }
            };

            return fields;
        }
    });
});
