define(
    ['jquery', 'core/module', 'core/ajaxmodal', 'routing', 'translator', 'template'],
    function ($, Module, AjaxModal, Routing, __, Template) {
        "use strict";

        return Module.extend({
            regions: {
                'tokenList': '#token-list'
            },
            initialize: function (options) {
                var collection = Backbone.Collection.extend({
                    url: Routing.generate('api_keys'),
                    model: Backbone.Model
                });

                var ApiCollection = new collection();

                ApiCollection.fetch();

                var view = Marionette.ItemView.extend({
                    template: Template['profile/api']
                });

                var collectionView = new Marionette.CollectionView({
                    collection: ApiCollection,
                    childView: view
                });

                this.app.getRegion('tokenList').show(collectionView);

                $('#create-api-token').on('click', this.createToken)
            },
            createToken: function (event) {
                event.preventDefault();

                var modal = AjaxModal.extend({
                    'modal': {
                        'title': __('profile.api.form.title'),
                        'buttons': {
                            'close': {
                                'class': 'warning',
                                'close': true,
                                'flat': true
                            },
                            'save': {
                                'class': 'success',
                                'save': true,
                                'flat': true
                            }
                        },
                        'events': {
                            'modal:save': 'saveApiToken'
                        }
                    },
                    'saveApiToken': function() {

                        this.showLoader();

                        var view = this;

                        $.ajax({
                            "url" : this.getOption('route'),
                            "data" : this.$('form').serialize(),
                            "type" : "post",
                            "success": function (response) {
                                view.trigger('ajax:response', response);

                                if (response.status !== 'success') {
                                    view.options.template = response.content;
                                    view.hideLoader();
                                    view.render();
                                } else {
                                    if (_.has(view, 'model')) {
                                        view.model.fetch({"success": function () {
                                            view.$el.modal('hide');
                                        }});
                                    } else {
                                        view.$el.modal('hide');
                                    }
                                }
                            }
                        });
                    }
                });

                new modal({
                    route: Routing.generate('api_key_create')
                });
            }
        });
});
