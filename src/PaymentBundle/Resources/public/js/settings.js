import Controller from './payment/controller';
import ItemView from 'SolidInvoiceCore/js/view';
import Module from 'SolidInvoiceCore/js/module';
import PaymentModel from './payment/model';
import MenuTemplate from '../templates/menu.hbs';
import AppRouter from "marionette.approuter";
import { clone, each, head, isUndefined, merge, values } from 'lodash';

export default Module.extend({
    regions: {
        'paymentMethodData': '#payment-method-settings',
        'paymentMethodList': '.left-sidebar'
    },
    initialize () {
        const model = new PaymentModel,
            controller = new Controller(this, model);

        const view = ItemView.extend({
            template: MenuTemplate,
            el: '#payment-method-tabs',
            router: null,
            modelEvents: {
                sync: 'render'
            },
            initialize: function() {
                this.model.fetch({ success: () => this.setRoutes() });
            },
            setRoutes: function() {
                const router = this.getOption('router'),
                    enabled = clone(this.model.get('enabled')),
                    disabled = clone(this.model.get('disabled'));

                let initialRoute = head(values(enabled));

                if (isUndefined(initialRoute)) {
                    initialRoute = head(values(disabled));
                }

                each(merge(enabled, disabled), function(item) {
                    router.appRoute(item, 'showMethod');
                });

                setTimeout(function() {
                    controller.showMethod(initialRoute);
                }, 0);
            }
        });

        const menuView = new view({
            model: model,
            router: new AppRouter({
                controller: Controller(this, model)
            })
        });

        this.app.showChildView('paymentMethodList', menuView);
    }
});
