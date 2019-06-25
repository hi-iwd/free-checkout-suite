define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/error-processor',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/totals'
    ],
    function ($,
              ko,
              quote,
              urlBuilder,
              errorProcessor,
              storage,
              messageList,
              $t,
              fullScreenLoader,
              getPaymentInformationAction,
              totals) {
        'use strict';
        return function (usedAmount, isApplied) {
            var message = $t('Your store credits were successfully applied');
            messageList.clear();
            fullScreenLoader.startLoader();
            return storage.post(
                urlBuilder.createUrl('/carts/mine/balance/apply', {})
            ).done(
                function (response) {
                    if (response) {
                        var deferred = $.Deferred();
                        totals.isLoading(true);
                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                            isApplied(true);
                            fullScreenLoader.stopLoader();
                        });
                        messageList.addSuccessMessage({'message': message});
                    } else {
                        fullScreenLoader.stopLoader();
                    }
                }
            ).fail(
                function (response) {
                    totals.isLoading(false);
                    errorProcessor.process(response);
                    fullScreenLoader.stopLoader();
                }
            )
        };
    }
);
