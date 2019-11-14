import { CollectionView } from 'backbone.marionette';
import RowModel from '../model/row_model';
import RowView from './item_row';

export default CollectionView.extend({
    childView: RowView,
    el: null,
    footerView: null,
    counter: 0,
    hasTax: null,
    templateContext () {
        return { hasTax: this.hasTax };
    },
    initialize (options) {
        this.footerView = options.footerView;
        this.el = options.selector;
        this.fieldData = options.fieldData;
        this.counter = this.collection.size();
        this.hasTax = options.hasTax;
    },
    ui: {
        'addItem': '.add-item'
    },
    events: {
        'click @ui.addItem': 'addItem'
    },
    collectionEvents: {
        'update:totals': 'renderTotals'
    },
    renderTotals () {
        const footer = this.$(this.el);

        setTimeout(() => {
            footer.empty();
            // eslint-disable-next-line
            this.footerView.render().$el.find('tr').appendTo(footer);
        }, 0);
    },
    onRender () {
        // eslint-disable-next-line
        this.footerView.render().$el.find('tr').appendTo(this.$(this.el));
    },
    addItem (event) {
        event.preventDefault();

        this.collection.add(new RowModel({
            id: this.counter++,
            fields: this.fieldData
        }));
    }
});
