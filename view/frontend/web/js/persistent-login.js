define([
    'uiComponent',
    "jquery",
    'Magento_Customer/js/customer-data',
    'mage/url',
    'jquery/jquery.cookie'
], function (Component, $, customerData, url) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.customer = customerData.get('customer');

            if ($.cookie('persistent_login_used') === undefined || !$.cookie('persistent_login_used')) {
                return;
            }

            if (this.isLoggedIn()) {
                return;
            }

            var customerDataUrl = url.build('customer/section/load') + '?sections=customer';

            $.ajax({
                url: customerDataUrl
            })
            .success(function(customerDataFromBackend) {
                if(customerDataFromBackend.customer.firstname !== undefined) {
                    return;
                }

                var persistentLoginUrl = url.build('persistent_login/persistent/login');

                $.post(persistentLoginUrl, function (data) {
                    if (data['refresh_page'] !== undefined && data['refresh_page']) {
                        window.location.reload(true);
                    }
                });
            })
        },

        isLoggedIn: function () {
            var customerInfo = customerData.get('customer')();
            var customerFirstname = customerInfo.firstname;

            return customerFirstname !== undefined;
        }

    });
});
