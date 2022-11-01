import { View } from 'backbone.marionette';
import './lib/bootstrap/modalmanager';

export default View.extend({
    constructor(options) {
        this.listenTo(this, 'render', () => {
            setTimeout(() => {
                const $tooltip = this.$('*[rel=tooltip]');
                if ($tooltip.length) {
                    import('bootstrap').then(() => {
                        $tooltip.tooltip();
                    });
                }
            }, 0);
        });

        View.call(this, options);

        this.listenTo(this.model, 'sync', this.render);
    },
    showLoader () {
        this.$el.modalmanager('loading');
    },
    hideLoader () {
        this.$el.modalmanager('removeLoading');
    }
});
