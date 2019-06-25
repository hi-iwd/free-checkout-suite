define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'Magento_Catalog/js/price-utils',
        'uiRegistry',
        'mage/translate',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'IWD_Opc/js/model/payment/is-loading',
        'Magento_Checkout/js/model/shipping-rate-service',
        'iwdOpcHelper',
        'mage/validation'
    ],
    function ($,
              _,
              Component,
              ko,
              customer,
              addressList,
              addressConverter,
              quote,
              createShippingAddress,
              selectShippingAddress,
              shippingRatesValidator,
              shippingService,
              selectShippingMethodAction,
              rateRegistry,
              setShippingInformationAction,
              checkoutDataResolver,
              checkoutData,
              priceUtils,
              registry,
              $t,
              totalsDefaultProvider,
              paymentIsLoading) {
        'use strict';

        var inlineAddress = "",
            newAddressOption = {
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        }, addressOptions = addressList().filter(function (address) {
            var isDublicate = inlineAddress === address.getAddressInline();
                inlineAddress = address.getAddressInline();
            return address.getType() === 'customer-address' && !isDublicate;
        });
        addressOptions.push(newAddressOption);

        var setShippingInformationTimeout = null,
            getTotalsTimeout = null,
            instance = null,
            totalsProcessors = [];

        return Component.extend({
            defaults: {
                template: 'IWD_Opc/shipping'
            },
            canHideErrors: true,
            isCustomerLoggedIn: customer.isLoggedIn,
            customerHasAddresses: addressOptions.length > 1,
            isAddressFormVisible: ko.observable(addressList().length === 0),
            saveInAddressBook: 1,
            addressOptions: addressOptions,
            selectedAddress: ko.observable(),
            displayAllMethods: window.checkoutConfig.iwdOpcSettings.displayAllMethods,
            specificMethodsForDisplayAllMethods: ['iwdstorepickup'],

            quoteIsVirtual: quote.isVirtual(),

            isShowGiftMessage: quote.isShowGiftMessage(),
            isShowDelimiterAfterShippingMethods: quote.isShowComment() || quote.isShowGiftMessage(),
            isShowComment: quote.isShowComment(),
            commentValue: ko.observable(checkoutData.getComment()),


            rateBuilding: ko.observable(false),
            shippingRateGroups: ko.observableArray([]),
            shippingRates: ko.observableArray([]),
            shippingRate: ko.observable(),
            shippingRateGroup: ko.observable(),
            rates: shippingService.getShippingRates(),
            shippingRateGroupsCaption: ko.observable(null),
            shippingRatesCaption: ko.observable(null),
            isShippingRatesVisible: ko.observable(false),

            isRatesLoading: shippingService.isLoading,
            initialize: function () {
                var self = this,
                    fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';
                this._super();

                instance = this;
                shippingRatesValidator.initFields(fieldsetName);
                checkoutDataResolver.resolveShippingAddress();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();
                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }

                    checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                    });
                });

                totalsProcessors['default'] = totalsDefaultProvider;

                if (addressList().length !== 0) {
                    this.selectedAddress.subscribe(function (addressId) {
                        if (typeof addressId === 'undefined' || addressId === '') { addressId = null; }
                        var address = _.filter(self.addressOptions, function (address) {
                            return address.customerAddressId === addressId;
                        })[0];
                        self.isAddressFormVisible(address === newAddressOption);
                        if (address && address.customerAddressId) {
                            if (quote.shippingAddress() && quote.shippingAddress().getKey() === address.getKey()) {
                                return;
                            }

                            selectShippingAddress(address);
                            checkoutData.setSelectedShippingAddress(address.getKey());
                        } else {
                            var addressData,
                                newShippingAddress;
                            addressData = self.source.get('shippingAddress');
                            addressData.save_in_address_book = self.saveInAddressBook ? 1 : 0;
                            newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                            selectShippingAddress(newShippingAddress);
                            checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                            checkoutData.setNewCustomerShippingAddress(addressData);
                        }
                    });

                    if (quote.shippingAddress()) {
                        this.selectedAddress(quote.shippingAddress().customerAddressId);
                    }
                }

                this.commentValue.subscribe(function (value) {
                    checkoutData.setComment(value);
                });

                quote.shippingMethod.subscribe(function (shippingMethod) {
                    clearTimeout(setShippingInformationTimeout);
                    if (shippingMethod) {
                        var carrierTitle = self.formatCarrierTitle(shippingMethod);
                        self.shippingRateGroup(carrierTitle);
                        self.shippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);
                    } else {
                        self.shippingRateGroup('');
                        self.shippingRate('');
                    }

                    clearTimeout(setShippingInformationTimeout);
                    clearTimeout(getTotalsTimeout);
                    if (shippingMethod) {
                        setShippingInformationTimeout = setTimeout(function () {
                            clearTimeout(getTotalsTimeout);
                            self.setShippingInformation();
                        }, 400);
                    } else {
                        getTotalsTimeout = setTimeout(function () {
                            clearTimeout(setShippingInformationTimeout);
                            var type = quote.shippingAddress().getType();
                            totalsProcessors[type]
                                ? totalsProcessors[type].estimateTotals(quote.shippingAddress())
                                : totalsProcessors['default'].estimateTotals(quote.shippingAddress());
                        }, 400);
                    }
                    self.validateShippingInformation();
                });

                this.rates.subscribe(function (rates) {
                    self.rateBuilding(true);
                    self.shippingRateGroups([]);
                    if (rates.length > 1) {
                        self.shippingRateGroupsCaption('');
                    } else {
                        self.shippingRateGroupsCaption(null);
                    }

                    _.each(rates, function (rate) {
                        if (rate) {
                            var carrierTitle = self.formatCarrierTitle(rate);
                            if (rate.error_message || !rate.method_code) {
                                self.rates.remove(rate);
                            }

                            if (self.shippingRateGroups.indexOf(carrierTitle) === -1) {
                                self.shippingRateGroups.push(carrierTitle);
                            }
                        }
                    });
                    self.rateBuilding(false);
                });

                this.shippingRateGroup.subscribe(function (carrierTitle) {
                    if (carrierTitle == '') {
                        return;
                    }

                    self.shippingRates([]);
                    var ratesByGroup = _.filter(self.rates(), function (rate) {
                        return carrierTitle === self.formatCarrierTitle(rate);
                    });

                    if (ratesByGroup.length === 0) {
                        self.selectShippingMethod('');
                    }

                    if (ratesByGroup.length > 1) {
                        self.shippingRatesCaption('');
                    } else {
                        self.shippingRatesCaption(null);
                    }

                    var $selectize = $('#iwd_opc_shipping_method_rates').length
                        ? $('#iwd_opc_shipping_method_rates')[0].selectize
                        : false;

                    if ($selectize) {
                        $selectize.loadedSearches = {};
                        $selectize.userOptions = {};
                        $selectize.renderCache = {};
                        $selectize.options = $selectize.sifter.items = {};
                        $selectize.lastQuery = null;
                        $selectize.updateOriginalInput({silent: true});
                    }

                    _.each(ratesByGroup, function (rate) {
                        if (self.shippingRates.indexOf(rate) === -1) {
                            rate = self.formatShippingRatePrice(rate);
                            self.shippingRates.push(rate);

                            if (rate.available && $selectize) {
                                $selectize.addOption({text: self.shippingRateTitle(rate), value: rate.carrier_code + '_' + rate.method_code})
                            }
                        }
                    });

                    if ($selectize) {
                        $selectize.refreshOptions(false);
                        $selectize.refreshItems();

                        if (ratesByGroup.length) {
                            $selectize.addItem(ratesByGroup[0].carrier_code + '_' + ratesByGroup[0].method_code);
                        }
                    }
                });

                this.shippingRates.subscribe(function (rate) {
                    var minLength = (self.displayAllMethods) ? 1 : 0;

                    if (self.shippingRates().length > minLength) {
                        self.isShippingRatesVisible(true);
                    } else {
                        self.isShippingRatesVisible(false);
                    }
                });

                return this;
            },

            optionsRenderCallback: [],

            decorateSelect: function (uid) {
                if (typeof(this.optionsRenderCallback[uid]) !== 'undefined') {
                    clearTimeout(this.optionsRenderCallback[uid]);
                }

                this.optionsRenderCallback[uid] = setTimeout(function () {
                    var select = $('#' + uid);
                    if (select.length) {
                        select.decorateSelectCustom();
                    }
                }, 0);
            },

            formatShippingRatePrice: function (rate) {
                if (rate.price_excl_tax !== 0 && rate.price_incl_tax !== 0) {
                    if (window.checkoutConfig.isDisplayShippingBothPrices && (rate.price_excl_tax !== rate.price_incl_tax)) {
                        rate.formatted_price = priceUtils.formatPrice(rate.price_excl_tax, quote.getPriceFormat());
                        rate.formatted_price += ' (' + $t('Incl. Tax') + ' ' + priceUtils.formatPrice(rate.price_incl_tax, quote.getPriceFormat()) + ')';
                    } else {
                        if (window.checkoutConfig.isDisplayShippingPriceExclTax) {
                            rate.formatted_price = priceUtils.formatPrice(rate.price_excl_tax, quote.getPriceFormat());
                        } else {
                            rate.formatted_price = priceUtils.formatPrice(rate.price_incl_tax, quote.getPriceFormat());
                        }
                    }
                }

                return rate;
            },

            setShippingInformation: function () {
                if (this.isAddressFormVisible()) {
                    var shippingAddress,
                        addressData;
                    shippingAddress = quote.shippingAddress();
                    addressData = addressConverter.formAddressDataToQuoteAddress(
                        this.source.get('shippingAddress')
                    );

                    for (var field in addressData) {
                        if (addressData.hasOwnProperty(field) &&
                            shippingAddress.hasOwnProperty(field) &&
                            typeof addressData[field] !== 'function' &&
                            _.isEqual(shippingAddress[field], addressData[field])
                        ) {
                            shippingAddress[field] = addressData[field];
                        } else if (typeof addressData[field] !== 'function' &&
                            !_.isEqual(shippingAddress[field], addressData[field])) {
                            shippingAddress = addressData;
                            break;
                        }
                    }

                    if (customer.isLoggedIn()) {
                        shippingAddress.save_in_address_book = this.saveInAddressBook ? 1 : 0;
                    }

                    checkoutData.setNeedEstimateShippingRates(false);
                    selectShippingAddress(shippingAddress);
                    if (customer.isLoggedIn()) {
                        checkoutData.setNewCustomerShippingAddress(shippingAddress);
                    }

                    checkoutData.setNeedEstimateShippingRates(true);
                }

                if (quote.shippingMethod()) {
                    paymentIsLoading.isLoading(true);
                    return setShippingInformationAction().always(function () {
                        paymentIsLoading.isLoading(false);
                    });
                } else {
                    return $.Deferred();
                }
            },

            textareaAutoSize: function (element) {
                $(element).textareaAutoSize();
            },

            shippingRateTitle: function (rate) {
                var title = '';
                if (rate) {
                    if (rate.formatted_price) {
                        title += rate.formatted_price + ' - ';
                    }
                }

                title += rate.method_title;

                return title;
            },

            shippingRateTitleFull: function(rate) {
                var title = this.shippingRateTitle(rate);
                if (rate.carrier_title) {
                    title += ': ' + rate.carrier_title;
                }

                return title;
            },

            formatCarrierTitle: function (rate) {
                var carrierTitle = rate['carrier_title'];

                if (this.displayAllMethods && this.specificMethodsForDisplayAllMethods.indexOf(rate.carrier_code)) {
                    rate = this.formatShippingRatePrice(rate);
                    carrierTitle = this.shippingRateTitleFull(rate);
                }

                return carrierTitle
            },

            addressOptionsText: function (address) {
                return address.getAddressInline();
            },

            selectShippingMethod: function (shippingMethod, shippingRates) {
                if (instance.rateBuilding()) {
                    return;
                }

                if (shippingMethod) {
                    var shippingMethodObject = _.filter(shippingRates, function (rate) {
                        return rate.carrier_code + '_' + rate.method_code === shippingMethod;
                    });
                    selectShippingMethodAction(shippingMethodObject[0]);
                    checkoutData.setSelectedShippingRate(shippingMethod);
                } else if (shippingMethod === '') {
                    selectShippingMethodAction(null);
                    checkoutData.setSelectedShippingRate(null);
                    quote.setDefaultShippingMethod(null);
                    quote.setSelectedShippingMethod(null);
                }

                return true;
            },

            validateShippingInformation: function (showErrors) {
                var loginFormSelector = 'form[data-role=email-with-possible-login]',
                    self = this,
                    emailValidationResult = customer.isLoggedIn(),
                    shippingMethodValidationResult = true;
                showErrors = showErrors || false;
                var shippingMethodForm = $('#co-shipping-method-form'),
                    shippingMethodSelectors = shippingMethodForm.find('.select');
                shippingMethodSelectors.removeClass('mage-error');
                shippingMethodForm.validate({
                    errorClass: 'mage-error',
                    errorElement: 'div',
                    meta: 'validate'
                });
                shippingMethodForm.validation();
                //additional validation for non-selected shippingMethod
                if(showErrors && !quote.shippingMethod()) {
                    shippingMethodSelectors.addClass('mage-error');
                }

                if (!shippingMethodForm.validation('isValid') || !quote.shippingMethod()) {
                    if (!showErrors && this.canHideErrors && shippingMethodForm.length) {
                        shippingMethodForm.validate().resetForm();
                    }

                    shippingMethodValidationResult = false;
                }

                if (!customer.isLoggedIn() && $(loginFormSelector).length) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                    if (!showErrors && this.canHideErrors) {
                        $(loginFormSelector).validate().resetForm();
                    }
                }

                if (this.isAddressFormVisible()) {
                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');

                    if (this.source.get('shippingAddress.custom_attributes')) {
                        this.source.trigger('shippingAddress.custom_attributes.data.validate');
                    }

                    if (this.source.get('params.invalid') ||
                        !quote.shippingMethod() ||
                        !emailValidationResult ||
                        !shippingMethodValidationResult
                    ) {
                        if (!showErrors && this.canHideErrors) {
                            var shippingAddress = this.source.get('shippingAddress');
                            shippingAddress = _.extend({
                                region_id: '',
                                region_id_input: '',
                                region: ''
                            }, shippingAddress);
                            _.each(shippingAddress, function (value, index) {
                                self.hideErrorForElement(value, index);
                            });
                            this.source.set('params.invalid', false)
                        }

                        return false;
                    }
                }

                return emailValidationResult && shippingMethodValidationResult;
            },
            hideErrorForElement: function (value, index) {
                var self = this;
                if (typeof(value) === 'object') {
                    _.each(value, function (childValue, childIndex) {
                        var newIndex = (index === 'custom_attributes' ? childIndex : index + '.' + childIndex);
                        self.hideErrorForElement(childValue, newIndex);
                    })
                }

                var fieldObj = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.' + index);
                if (fieldObj) {
                    if (typeof (fieldObj.error) === 'function') {
                        fieldObj.error(false);
                    }
                }
            }
        });
    }
);
