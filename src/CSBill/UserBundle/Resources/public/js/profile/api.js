define(
    ['jquery', 'lodash', 'backbone', 'marionette', 'core/module', 'profile/view/modal/create', 'routing', 'template', 'profile/view/token'],
    function($, _, Backbone, Mn, Module, CreateModal, Routing, Template, TokenView) {
        "use strict";

        return Module.extend({
            regions: {
                'tokenList': '#token-list'
            },
            collection: null,
            initialize: function() {
                var collection = Backbone.Collection.extend({
                    url: Routing.generate('api_keys'),
                    model: Backbone.Model
                });

                this.collection = new collection();
                this.collection.fetch();

                var collectionView = new Mn.CollectionView({
                    collection: this.collection,
                    childView: TokenView,
                    emptyView: Mn.ItemView.extend({
                        template: Template['profile/empty_tokens']
                    })
                });

                this.app.getRegion('tokenList').show(collectionView);

                $('#create-api-token').on('click', _.bind(this.createToken, this))
            },
            createToken: function(event) {
                event.preventDefault();

                var modal = new CreateModal({
                    route: Routing.generate('api_key_create')
                });

                this.listenTo(modal, 'ajax:response', _.bind(function (response) {
                    this.collection.add(response.token);
                }, this));
            }
        });
    });
