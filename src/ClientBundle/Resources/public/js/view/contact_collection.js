import { CollectionView } from "backbone.marionette";
import ContactView from './contact';

export default CollectionView.extend({
    childView: ContactView
});
