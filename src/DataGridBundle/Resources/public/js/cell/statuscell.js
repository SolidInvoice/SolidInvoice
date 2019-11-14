import $ from 'jquery';
import Backgrid from 'backgrid';
import { each, startCase } from 'lodash';

const Labels = JSON.parse($('script[data-type="status_labels"]').text());

const statusCell = function(name, labels) {
    return Backgrid.Cell.extend({
        render () {
            this.$el.empty();
            const rawValue = this.model.get(this.column.get('name'));
            const formattedValue = this.formatter.fromRaw(rawValue, this.model);
            this.$el.append(labels[formattedValue]);
            this.delegateEvents();
            return this;
        }
    });
};

each(Labels, (labels, name) => {
    const cellName = startCase(name) + '_statusCell';
    Backgrid[cellName] = statusCell(name, labels);
});

export default statusCell;
