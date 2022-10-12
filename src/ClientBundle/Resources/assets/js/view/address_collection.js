import AddressView from './address';
import { CollectionView } from 'backbone.marionette';

export default CollectionView.extend({
    childView: AddressView,

    initialize () {
        this.listenTo(this.collection, 'remove', this.render);
        this.listenTo(this.collection, 'add', this.render);
    }
});
