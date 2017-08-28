define(
    ['ko'],
    function (ko) {
        'use strict';
        var billingAddress = ko.observable(null);
        var shippingAddress = ko.observable(null);
        var shippingMethod = ko.observable(null);
        var paymentMethod = ko.observable(null);
        var quoteData = window.checkoutConfig.quoteData;
        var basePriceFormat = window.checkoutConfig.basePriceFormat;
        var priceFormat = window.checkoutConfig.priceFormat;
        var storeCode = window.checkoutConfig.storeCode;
        var totalsData = window.checkoutConfig.totalsData;
        var totals = ko.observable(totalsData);
        var collectedTotals = ko.observable({});
        return {
            totals: totals,
            shippingAddress: shippingAddress,
            shippingMethod: shippingMethod,
            billingAddress: billingAddress,
            paymentMethod: paymentMethod,
            guestEmail: null,

            isCustomerLoggedIn: function () {
                return window.checkoutConfig.isCustomerLoggedIn;
            },
            getCustomerGroupId: function () {
                return quoteData.customer_group_id;
            },
            getPaymentTitleType: function () {
                return window.checkoutConfig.iwdOpcSettings.paymentTitleType;
            },
            getPaymentImagePath: function (type) {
                return window.checkoutConfig.iwdOpcSettings.paymentLogosImages[type];
            },
            isRestrictPaymentEnable: function () {
                return window.checkoutConfig.iwdOpcSettings.isRestrictPaymentEnable;
            },
            getRestrictedPaymentMethods: function () {
                return window.checkoutConfig.iwdOpcSettings.restrictedPaymentMethods;
            },
            getDefaultShippingMethod: function () {
                return window.checkoutConfig.iwdOpcSettings.defaultShippingMethod;
            },
            getDefaultPaymentMethod: function () {
                return window.checkoutConfig.iwdOpcSettings.defaultPaymentMethod;
            },
            getSelectedShippingMethod: function () {
                return window.checkoutConfig.selectedShippingMethod;
            },
            setDefaultShippingMethod: function (defaultShippingMethod) {
                window.checkoutConfig.iwdOpcSettings.defaultShippingMethod = defaultShippingMethod;
            },
            setSelectedShippingMethod: function (selectedShippingMethod) {
                return window.checkoutConfig.selectedShippingMethod = selectedShippingMethod;
            },
            getLogoutUrl: function () {
                return window.checkoutConfig.iwdOpcSettings.logoutUrl;
            },
            getForgotPasswordUrl: function () {
                return window.checkoutConfig.iwdOpcSettings.forgotPasswordUrl;
            },
            isShowLoginButton: function () {
                return window.checkoutConfig.iwdOpcSettings.isShowLoginButton;
            },
            isShowGiftMessage: function () {
                return window.checkoutConfig.iwdOpcSettings.isShowGiftMessage;
            },
            isShowComment: function () {
                return window.checkoutConfig.iwdOpcSettings.isShowComment;
            },
            isShowDiscount: function () {
                return window.checkoutConfig.iwdOpcSettings.isShowDiscount;
            },
            isShowSubscribe: function () {
                return window.checkoutConfig.iwdOpcSettings.isShowSubscribe;
            },
            isReloadShippingOnDiscount: function () {
                return window.checkoutConfig.iwdOpcSettings.isReloadShippingOnDiscount;
            },
            isSubscribeByDefault: function () {
                return window.checkoutConfig.iwdOpcSettings.isSubscribeByDefault;
            },
            getQuoteId: function () {
                return quoteData.entity_id;
            },
            isVirtual: function () {
                return !!Number(quoteData.is_virtual);
            },
            getPriceFormat: function () {
                return priceFormat;
            },
            getBasePriceFormat: function () {
                return basePriceFormat;
            },
            getItems: function () {
                return window.checkoutConfig.quoteItemData;
            },
            getTotals: function () {
                return totals;
            },
            setTotals: function (totalsData) {
                if (_.isObject(totalsData) && _.isObject(totalsData.extension_attributes)) {
                    _.each(totalsData.extension_attributes, function (element, index) {
                        totalsData[index] = element;
                    });
                }
                totals(totalsData);
                this.setCollectedTotals('subtotal_with_discount', parseFloat(totalsData.subtotal_with_discount));
            },
            setPaymentMethod: function (paymentMethodCode) {
                paymentMethod(paymentMethodCode);
            },
            getPaymentMethod: function () {
                return paymentMethod;
            },
            getStoreCode: function () {
                return storeCode;
            },
            setCollectedTotals: function (code, value) {
                var totals = collectedTotals();
                totals[code] = value;
                collectedTotals(totals);
            },
            getCalculatedTotal: function () {
                var total = 0.;
                _.each(collectedTotals(), function (value) {
                    total += value;
                });
                return total;
            }
        };
    }
);
