define(
    [
        'jquery',
        'uiRegistry'
    ],
    function ($, registry) {
        'use strict';

        return {
            validate: function () {
                var paymentMethodSelected = $('#co-payment-form').validate({
                    errorClass: 'mage-error',
                    errorElement: 'div',
                    meta: 'validate'
                }).element($('#iwd_opc_payment_method_select'));
                var paymentMethodFormValid = true;
                if (paymentMethodSelected) {
                    var activeForm = $('#co-payment-form').find('.payment-method._active form:not(.co-billing-form)').first();
                    if (activeForm.length) {
                        activeForm.validate({
                            errorClass: 'mage-error',
                            errorElement: 'div',
                            meta: 'validate'
                        });
                        activeForm.validation();
                        paymentMethodFormValid = activeForm.validation('isValid');
                    }
                }

                return paymentMethodSelected && paymentMethodFormValid;
            }
        };
    }
);
