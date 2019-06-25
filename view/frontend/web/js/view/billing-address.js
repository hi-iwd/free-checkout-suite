define(
    [
        'jquery',
        'ko',
        'underscore',
        'Magento_Ui/js/form/form',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'uiRegistry',
        'Magento_Checkout/js/model/postcode-validator',
        'Magento_Checkout/js/model/address-converter'
    ],
    function ($,
              ko,
              _,
              Component,
              customer,
              addressList,
              quote,
              createBillingAddress,
              selectBillingAddress,
              checkoutData,
              checkoutDataResolver,
              customerData,
              setBillingAddressAction,
              globalMessageList,
              $t,
              registry,
              postcodeValidator,
              addressConverter) {
        'use strict';

        var observedElements = [],
            setBillingActionTimeout = 0,
            inlineAddress = "",
            newAddressOption = {
                /**
                 * Get new address label
                 * @returns {String}
                 */
                getAddressInline: function () {
                    return $t('New Address');
                },
                customerAddressId: null
            },
            countryData = customerData.get('directory-data'),
            addressOptions = addressList().filter(function (address) {
                var isDublicate = inlineAddress === address.getAddressInline();
                inlineAddress = address.getAddressInline();
                return address.getType() === 'customer-address' && !isDublicate;
            });

        addressOptions.push(newAddressOption);

        return Component.extend({
            defaults: {
                template: 'IWD_Opc/billing-address'
            },
            canHideErrors: true,
            postcodeElement: null,
            addressOptions: addressOptions,
            customerHasAddresses: addressOptions.length > 1,
            selectedAddress: ko.observable(null),

            quoteIsVirtual: quote.isVirtual(),
            isAddressFormVisible: ko.observable((addressList().length === 0 || (checkoutData.getSelectedBillingAddress() === 'new-customer-address' && !!checkoutData.getNewCustomerBillingAddress()))),
            isAddressSameAsShipping: ko.observable(!checkoutData.getSelectedBillingAddress()),
            saveInAddressBook: ko.observable(true),
            canUseShippingAddress: ko.computed(function () {
                return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
            }),

            optionsRenderCallback: 0,
            validateAddressTimeout: 0,
            validateDelay: 1400,

            decorateSelect: function (uid, showEmptyOption) {
                if (typeof showEmptyOption === 'undefined') { showEmptyOption = false; }
                clearTimeout(this.optionsRenderCallback);
                this.optionsRenderCallback = setTimeout(function () {
                    var select = $('#' + uid);
                    if (select.length) {
                        select.decorateSelect(showEmptyOption, true);
                    }
                }, 0);
            },
            /**
             * Get code
             * @param {Object} parent
             * @returns {String}
             */
            getCode: function (parent) {
                return (parent && _.isFunction(parent.getCode)) ? parent.getCode() : 'shared';
            },
            getNameForSelect: function () {
                return this.name.replace(/\./g, '');
            },
            getCountryName: function (countryId) {
                return countryData()[countryId] !== undefined ? countryData()[countryId].name : '';
            },
            /**
             * @param {Object} address
             * @return {*}
             */
            addressOptionsText: function (address) {
                return address.getAddressInline();
            },

            /**
             * Init component
             */
            initialize: function () {
                var self = this;
                this._super();
                if (quote.isVirtual()) {
                    self.isAddressSameAsShipping(false);
                }

                quote.shippingAddress.subscribe(function (address) {
                    if (self.isAddressSameAsShipping()) {
                        var billingAddress = $.extend({}, address);
                        billingAddress.saveInAddressBook = 0;
                        billingAddress.save_in_address_book = 0;
                        selectBillingAddress(billingAddress);
                        var origAddress = self.source.get(self.dataScopePrefix),
                            convertedAddress = addressConverter.quoteAddressToFormAddressData(billingAddress);

                        $.each(origAddress, function(key, val) {
                            if (key === 'street') {
                                if (typeof convertedAddress[key] === 'undefined') {
                                    convertedAddress[key] = {};
                                }

                                $.each(origAddress[key], function(streetKey, streetVal) {
                                    if (typeof streetVal !== 'undefined' && typeof convertedAddress[key][streetKey] === 'undefined') {
                                        convertedAddress[key][streetKey] = '';
                                    }
                                });
                            } else if (typeof val !== 'undefined' && typeof convertedAddress[key] === 'undefined') {
                                convertedAddress[key] = '';
                            }
                        });

                        self.source.set(self.dataScopePrefix, convertedAddress);
                    }
                });

                quote.billingAddress.subscribe(function (address) {
                    if (address.customerAddressId) {
                        self.selectedAddress(address.customerAddressId);
                    } else {
                        self.selectedAddress('');
                    }

                    setTimeout(function () {
                        $('select[name=billing_address_id]').change();
                    }, 0);
                });

                self.isAddressSameAsShipping.subscribe(function (value) {
                    if (!value) {
                        $('.co-billing-form select').trigger('change');
                    }
                });

                if (addressList().length !== 0) {
                    this.selectedAddress.subscribe(function (addressId) {
                        if (!addressId) { addressId = null; }
                        if (!self.isAddressSameAsShipping()) {
                            var address = _.filter(self.addressOptions, function (address) {
                                return address.customerAddressId === addressId;
                            })[0];

                            self.isAddressFormVisible(address === newAddressOption);
                            if (address && address.customerAddressId) {
                                selectBillingAddress(address);
                                checkoutData.setSelectedBillingAddress(address.getKey());
                            } else {
                                var addressData,
                                    newBillingAddress;
                                var countrySelect = $('.co-billing-form:visible').first().find('select[name="country_id"]');
                                if (countrySelect.length) {
                                    var initialVal = countrySelect.val();
                                    countrySelect.val('').trigger('change').val(initialVal).trigger('change');
                                }

                                addressData = self.source.get(self.dataScopePrefix);
                                addressData.save_in_address_book = self.saveInAddressBook() && !self.isAddressSameAsShipping() ? 1 : 0;
                                newBillingAddress = createBillingAddress(addressData);
                                selectBillingAddress(newBillingAddress);
                                checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                                checkoutData.setNewCustomerBillingAddress(addressData);
                            }

                            self.setBillingAddress();
                        }
                    });
                }

                if (this.dataScopePrefix === 'billingAddressshared') {
                    checkoutDataResolver.resolveBillingAddress();
                    var billingAddressCode = this.dataScopePrefix;
                    setTimeout(function () {
                        registry.async('checkoutProvider')(function (checkoutProvider) {
                            var defaultAddressData = checkoutProvider.get(billingAddressCode);

                            if (defaultAddressData === undefined) {
                                return;
                            }

                            var billingAddressData = checkoutData.getBillingAddressFromData();

                            if (billingAddressData) {
                                checkoutProvider.set(
                                    billingAddressCode,
                                    $.extend(true, {}, defaultAddressData, billingAddressData)
                                );
                            }
                            checkoutProvider.on(billingAddressCode, function (providerBillingAddressData) {
                                checkoutData.setBillingAddressFromData(providerBillingAddressData);
                            }, billingAddressCode);
                        });
                    }, 200);
                }

                if (quote.isVirtual()) {
                    checkoutDataResolver.resolveBillingAddress();
                }

                self.initFields();
            },

            setBillingAddress: function () {
                clearTimeout(setBillingActionTimeout);
                setBillingActionTimeout = setTimeout(function () {
                    setBillingAddressAction(globalMessageList);
                }, 100);
            },

            useShippingAddress: function () {
                if (!this.isAddressSameAsShipping()) {
                    if (!checkoutDataResolver.applyBillingAddress()) {
                        if (this.validateFields()) {
                            this.setBillingAddress();
                        }
                    }
                } else {
                    checkoutData.setSelectedBillingAddress(null);
                    selectBillingAddress(quote.shippingAddress());
                }

                return true;
            },

            initFields: function () {
                var self = this;
                var formPath = self.name + '.form-fields';
                var elements = [
                    'country_id',
                    'postcode',
                    'region_id',
                    'region_input_id'
                ];
                _.each(elements, function (element) {
                    registry.async(formPath + '.' + element)(self.bindHandler.bind(self));
                });
            },

            bindHandler: function (element) {
                var self = this;
                var delay = self.validateDelay;
                if (element.index === 'postcode') {
                    self.postcodeElement = element;
                }

                if (element.component.indexOf('/group') !== -1) {
                    $.each(element.elems(), function (index, elem) {
                        self.bindHandler(elem);
                    });
                } else {
                    element.on('value', function () {
                        clearTimeout(self.validateAddressTimeout);
                        self.validateAddressTimeout = setTimeout(function () {
                            if (!self.isAddressSameAsShipping()) {
                                if (self.postcodeValidation()) {
                                    if (self.validateFields()) {
                                        self.setBillingAddress();
                                    }
                                }
                            }
                        }, delay);
                    });
                    observedElements.push(element);
                }
            },

            postcodeValidation: function () {
                var self = this;
                var countryId = $('.co-billing-form:visible').first().find('select[name="country_id"]').val(),
                    validationResult,
                    warnMessage;

                if (self.postcodeElement === null || self.postcodeElement.value() === null) {
                    return true;
                }

                self.postcodeElement.warn(null);
                validationResult = postcodeValidator.validate(self.postcodeElement.value(), countryId);

                if (!validationResult) {
                    warnMessage = $t('Provided Zip/Postal Code seems to be invalid.');

                    if (postcodeValidator.validatedPostCodeExample.length) {
                        warnMessage += $t(' Example: ') + postcodeValidator.validatedPostCodeExample.join('; ') + '. ';
                    }
                    warnMessage += $t('If you believe it is the right one you can ignore this notice.');
                    self.postcodeElement.warn(warnMessage);
                }

                return validationResult;
            },

            validateFields: function (showErrors) {
                showErrors = showErrors || false;
                var self = this;
                if (!this.isAddressFormVisible()) {
                    return true;
                }

                this.source.set('params.invalid', false);
                this.source.trigger(this.dataScopePrefix + '.data.validate');

                if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                    this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (!this.source.get('params.invalid')) {
                    var addressData = this.source.get(this.dataScopePrefix),
                        newBillingAddress;

                    // if (customer.isLoggedIn() && !this.customerHasAddresses && !self.isAddressSameAsShipping()) {
                    //     this.saveInAddressBook = 1;
                    // }

                    addressData['save_in_address_book'] = this.saveInAddressBook() && !self.isAddressSameAsShipping() ? 1 : 0;
                    newBillingAddress = createBillingAddress(addressData);

                    selectBillingAddress(newBillingAddress);
                    checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                    checkoutData.setNewCustomerBillingAddress(addressData);
                    return true;
                } else {
                    if (!showErrors && this.canHideErrors) {
                        var billingAddress = this.source.get(this.dataScopePrefix);
                        billingAddress = _.extend({
                            region_id: '',
                            region_id_input: '',
                            region: ''
                        }, billingAddress);
                        _.each(billingAddress, function (value, index) {
                            self.hideErrorForElement(value, index);
                        });
                        this.source.set('params.invalid', false);
                    }
                    return false;
                }
            },

            hideErrorForElement: function (value, index) {
                var self = this;
                if (typeof(value) === 'object') {
                    _.each(value, function (childValue, childIndex) {
                        var newIndex = (index === 'custom_attributes' ? childIndex : index + '.' + childIndex);
                        self.hideErrorForElement(childValue, newIndex);
                    })
                }

                var fieldObj = registry.get(self.name + '.form-fields.' + index);
                if (fieldObj) {
                    if (typeof (fieldObj.error) === 'function') {
                        fieldObj.error(false);
                    }
                }
            },

            collectObservedData: function () {
                var observedValues = {};

                $.each(observedElements, function (index, field) {
                    observedValues[field.dataScope] = field.value();
                });

                return observedValues;
            }
        });
    }
);
