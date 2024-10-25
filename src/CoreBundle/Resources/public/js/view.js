import { View } from 'backbone.marionette';

export default View.extend({
    constructor(options) {
        this.listenTo(this, 'render', () => {
            setTimeout(() => {
                const $select2 = this.$('select.select2');
                if ($select2.length) {
                    import('select2').then(() => {
                        $select2.select2({
                            allowClear: true
                        });
                    });
                }

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
