/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Module from 'SolidInvoiceCore/js/module';
import MailSettings from 'SolidInvoiceMailer/js/form/mail'

export default Module.extend({
    initialize(config) {
        new MailSettings('settings_email_sending_options', config.email.sending_options.provider);
    }
});
