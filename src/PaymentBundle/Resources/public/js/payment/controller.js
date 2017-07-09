define(
    ['jquery', 'marionette', 'backbone', 'lodash', 'routing', 'template'],
    function($, Mn, Backbone, _, Routing, Template) {
        "use strict";

        return function(module, model) {
            var LoaderView = Mn.View.extend({
                template: Template.payment.loader
            });

            return {
                showMethod: function(routeFragment) {
                    var fragment = Backbone.history.getFragment();

                    if (_.isEmpty(fragment)) {
                        fragment = routeFragment;
                    }

                    $('li', '#payment-method-tabs').removeClass('active');
                    $('a[data-method="' + fragment + '"]').closest('li').addClass('active');

                    var route = Routing.generate('_xhr_payments_settings', {'method': fragment});
                    module.app.showChildView('paymentMethodData', new LoaderView);

                    $.get(route, function(response) {
                        var view = Mn.View.extend({
                            template: response,
                            ui: {
                                'save': '#payment_methods_save'
                            },
                            events: {
                                'click @ui.save': 'saveMethod'
                            },
                            saveMethod: function(event) {
                                event.preventDefault();

                                module.app.showChildView('paymentMethodData', new LoaderView);

                                var form = this.$('form');
                                var data = form.serialize(),
                                    url = form.prop('action');

                                $.ajax({
                                    url: url,
                                    data: data,
                                    method: 'POST',
                                    success: function(response) {
                                        module.app.showChildView('paymentMethodData', new view({template: response}));
                                        model.fetch();
                                    }
                                });
                            }
                        });

                        module.app.showChildView('paymentMethodData', new view);
                    });
                }
            };
        };
    });