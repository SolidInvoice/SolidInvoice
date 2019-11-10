import Translator from 'bazinga-translator';
import Config from '~/config';

import('~/translations/' + Config.locale + '.json').then(({ default: messages}) => {
    Translator.fromJSON(messages);
});

export default Translator;
