define(
    ['jquery', 'marionette', 'backbone', 'lodash', 'routing', 'template'],
    function($, Mn, Backbone, _, Routing, Template) {
        "use strict";

        return function(module, model) {
            var LoaderView = Mn.ItemView.extend({
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

                    var route = Routing.generate('_payment_method_settings', {'method': fragment});
                    module.app.getRegion('paymentMethodData').show(new LoaderView);

                    $.get(route, function(response) {
                        var view = response.content;

                        var ItemView = Mn.ItemView.extend({
                            template: view,
                            ui: {
                                'save': '#payment_methods_save'
                            },
                            events: {
                                'click @ui.save': 'saveMethod'
                            },
                            saveMethod: function(event) {
                                event.preventDefault();

                                module.app.getRegion('paymentMethodData').show(new LoaderView);

                                var form = this.$('form');
                                var data = form.serialize(),
                                    url = form.prop('action');

                                $.ajax({
                                    url: url,
                                    data: data,
                                    method: 'POST',
                                    success: function(response) {
                                        module.app.getRegion('paymentMethodData').show(new ItemView({template: response.content}));
                                        model.fetch();
                                    }
                                });
                            },
                            onRender: function() {
                                setTimeout(function() {
                                    $.material.init();
                                }, 0);
                            }
                        });

                        module.app.getRegion('paymentMethodData').show(new ItemView);
                    });
                }
            };
        };
    });