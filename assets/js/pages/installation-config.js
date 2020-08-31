import MailConfig from 'SolidInvoiceMailer/js/form/mail'
import Config from '../config'

export default new MailConfig('config_step_email_settings_transport', Config.module.data.mail.settings.value);
