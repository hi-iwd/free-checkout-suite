define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'iwdOpcHelper'
    ],
    function ($,
              _,
              Component,
              ko,
              checkoutData,
              quote,
              $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'IWD_Opc/billing-step-virtual'
            },
            isShowComment: quote.isShowComment(),
            commentValue: ko.observable(checkoutData.getComment()),
            initialize: function () {
                this._super();
                this.commentValue.subscribe(function (value) {
                    checkoutData.setComment(value);
                });
            },
            textareaAutoSize: function (element) {
                $(element).textareaAutoSize();
            }
        });
    }
);
