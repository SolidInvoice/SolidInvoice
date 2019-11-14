import ItemView from 'SolidInvoiceCore/js/view';
import Template from '../../../templates/api.hbs';
import Translator from 'translator';
import AjaxModal from 'SolidInvoiceCore/js/ajaxmodal';
import Alert from 'SolidInvoiceCore/js/alert';

export default ItemView.extend({
    template: Template,
    ui: {
        'revokeBtn': '.revoke-token',
        'historyBtn': '.view-token-history'
    },
    events: {
        'click @ui.revokeBtn': 'revokeToken',
        'click @ui.historyBtn': 'showHistory'
    },
    revokeToken (event) {
        event.preventDefault();

        const model = this.model;
        Alert.confirm(Translator.trans('profile.api.tokens.revoke_message'), (result) => {
            if (result) {
                return model.destroy({ wait: true });
            }
        });
    },
    showHistory (event) {
        event.preventDefault();

        const modal = AjaxModal.extend({
            'modal': {
                'title': Translator.trans('profile.api.history.title'),
                'buttons': {
                    'Close': {
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
