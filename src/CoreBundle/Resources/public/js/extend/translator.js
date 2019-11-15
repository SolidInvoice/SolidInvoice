import Translator from 'bazinga-translator';
import Config from '~/config';
const messages = require('~/translations/' + Config.locale + '.json');

Translator.fromJSON(messages);

export default Translator;
