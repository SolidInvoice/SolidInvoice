import MailConfig from 'SolidInvoiceCore/js/util/form/mail'
import Config from '../config'

export default new MailConfig('config_step_email_settings', Config.moduleData.mail.settings.value);
