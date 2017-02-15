define(
    ['jquery', 'core/view', 'lodash', 'routing'],
    function($, ItemView, _, Routing) {
        "use strict";

        return ItemView.extend({
            clientForm: null,
            ui: {
                'clientChange': '#client-select-change',
                'clientSelect': '.client-select'
            },
            events: {
                'click @ui.clientChange': 'clientChange',
                'change @ui.clientSelect': 'clientSelect'
            },
            initialize: function() {
                this.template = _.bind(function () { return this.getOption('clientForm'); }, this);
            },
            onRender: function() {
                if (!this.model.isEmpty()) {
                    this.$('#client-select').hide();
                }
            },
            clientChange: function(event) {
                event.preventDefault();

                this._toggleContactInfo();
            },
            clientSelect: function(event) {
                if (_.isEmpty(event.val)) {
                    return;
                }

                if (parseInt(event.val, 10) === parseInt(this.model.id, 10)) {
                    this._toggleContactInfo();
                    return;
                }

                this.showLoader();

                $.getJSON(
                    Routing.generate('_clients_info', {id: event.val, type: this.getOption('type')}),
                    _.bind(function(data) {
                        this.$('#client-select-container').html(data.content);
                        this._toggleContactInfo(true);

                        $.material.init();

                        if (this.options.hideLoader) {
                            this.hideLoader();
                        }

                        if (data.currency) {
                            data.client = event.val;
                            this.trigger('currency:update', data);
                        }
                    }, this)
                );
            },
            _toggleContactInfo: function (show) {
                this.$('#client-select').toggle();

                if (!_.isUndefined(show)) {
                    if (true === show) {
                        this.$('#client-select-container').show();
                    } else {
                        this.$('#client-select-container').hide();
                    }
                } else {
                    this.$('#client-select-container').toggle();
                }
            }
        });
    });