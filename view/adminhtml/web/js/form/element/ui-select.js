define([
    'underscore',
    'Magento_Ui/js/lib/key-codes',
    'mage/translate',
    'ko',
    'jquery',
    'Magento_Ui/js/lib/view/utils/async'
], function(_, keyCodes, $t, ko, $) {
    'use strict';

    return function (target) {

        return target.extend({

            defaults: {
                customAttribute: null
            },

            url: '/keywordattributes/attribute/add',
            attributeName: 'select_keyword',
            customAttribute: ko.observable(false),
            parent: null,

            initialize: function () {
                this._super();
                var parent = this._super();
                this.parent = parent;
                return this;
            },
            submitNewOption: function () {
                var self = this;

                var area = window.areaFrontName;
                var url = '/' + area + self.url;

                if(!self.customAttribute) {
                    return false;
                } else {
                    self.customAttribute.trim();
                }

                $.ajax({
                    url: url,
                    type: 'post',
                    data: {
                        'attribute_name': self.attributeName,
                        'attribute_option': self.customAttribute
                    },
                    dataType: 'json'})
                    .done(function (data) {
                        if(data.value && data.label) {
                            $('#add_option input').attr('value', '');
                            self.parent.options.push({value: data.value, label: data.label, level: 1, path: ""});
                        }
                    }).always(function () {
                });
            }
        });
    }
});