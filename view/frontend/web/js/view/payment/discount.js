define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/shipping-rate-registry'
    ],
    function ($, ko, Component, quote, setCouponCodeAction, cancelCouponAction, priceUtils, rateRegistry) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'IWD_Opc/payment/discount'
            },
            isEnabled: quote.isShowDiscount(),
            totals: quote.getTotals(),
            couponCode: ko.observable(quote.getTotals()()['coupon_code']),

            /**
             * Applied flag
             */
            isApplied: ko.observable(!!quote.getTotals()()['coupon_code']),

            /**
             * Coupon code application procedure
             */
            apply: function () {
                if (this.validate()) {
                    setCouponCodeAction(this.couponCode(), this.isApplied).done(function () {
                        if (!quote.isVirtual() && quote.isReloadShippingOnDiscount()) {
                            rateRegistry.set(quote.shippingAddress().getCacheKey(), false);
                            quote.shippingAddress(quote.shippingAddress()); //trigger getRates
                        }
                    });
                }
            },

            /**
             * Cancel using coupon
             */
            remove: function () {
                if (this.validate()) {
                    this.couponCode('');
                    cancelCouponAction(this.isApplied).done(function () {
                        if (!quote.isVirtual() && quote.isReloadShippingOnDiscount()) {
                            rateRegistry.set(quote.shippingAddress().getCacheKey(), false);
                            quote.shippingAddress(quote.shippingAddress()); //trigger getRates
                        }
                    });
                }
            },

            cancel: function () {
                $('#iwd_opc_discount [data-role=trigger]').trigger('click');
            },

            getDiscountAmount: function () {
                var price = 0;
                if (this.totals() && this.totals().discount_amount) {
                    price = parseFloat(this.totals().discount_amount);
                }

                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            /**
             * Coupon form validation
             *
             * @returns {Boolean}
             */
            validate: function () {
                var form = '#discount-form';
                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
