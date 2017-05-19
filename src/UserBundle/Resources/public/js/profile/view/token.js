define(
    ['marionette', 'bootstrap.bootbox', 'core/ajaxmodal', 'template', 'translator'],
    function(Mn, Bootbox, AjaxModal, Template, __) {
        "use strict";

        return Mn.View.extend({
            template: Template.user.api,
            ui: {
                'revokeBtn': '.revoke-token',
                'historyBtn': '.view-token-history'
            },
            events: {
                'click @ui.revokeBtn': 'revokeToken',
                'click @ui.historyBtn': 'showHistory'
            },
            revokeToken: function(event) {
                event.preventDefault();

                var model = this.model;
                Bootbox.confirm(__('profile.api.tokens.revoke_message'), function(result) {
                    if (result) {
                        model.destroy();
                    }
                });
            },
            showHistory: function(event) {
                event.preventDefault();

                var modal = AjaxModal.extend({
                    'modal': {
                        'title': __('profile.api.history.title'),
                        'buttons': {
                            'close': {
                                'class': 'warning',
                                'close': true,
                                'flat': true
                            }
                        }
                    }
                });

                new modal({
                    route: event.target.href
                });
            }
        });
    });