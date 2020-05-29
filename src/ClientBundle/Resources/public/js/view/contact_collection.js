import { CollectionView } from 'backbone.marionette';
import ContactView from './contact';

export default CollectionView.extend({
    className: 'row',
    childView: ContactView
});
