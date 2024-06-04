import $ from 'jquery';
import Module from 'SolidInvoiceCore/js/module';
import Backbone from 'backbone';
import Routing from 'fos-router';
import { CollectionView, View } from 'backbone.marionette';
import Template from '../../templates/empty_tokens.hbs';
import TokenView from './view/token';
import CreateModal from './view/modal/create';
import { assignIn } from 'lodash'

export default Module.extend({
    regions: {
        'tokenList': '#token-list'
    },
    collection: null,
    initialize () {
        const collection = Backbone.Collection.extend({
            url: Routing.generate('_xhr_api_keys_list'),
            model: Backbone.Model.extend({
                defaults: {},
                destroy (options) {
                    const opts = assignIn({ url: Routing.generate('_xhr_api_keys_revoke', { 'id': this.id }) }, options || {});
                    return Backbone.Model.prototype.destroy.call(this, opts);
                }
            })
        });

        this.collection = new collection();
        this.collection.fetch();

        const collectionView = new CollectionView({
            collection: this.collection,
            childView: TokenView,
            emptyView: View.extend({
                template: Template
            })
        });

        this.app.showChildView('tokenList', collectionView);

        $('#create-api-token').on('click', (event) => this.createToken(event))
    },
    createToken (event) {
        event.preventDefault();

        const modal = new CreateModal({
            route: Routing.generate('_xhr_api_keys_create')
        });

        this.listenTo(modal, 'ajax:response', (response) => {
            this.collection.add(response);
        });
    }
});
