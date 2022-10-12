import Translator from 'bazinga-translator';

const messages = require('~/translations/' + Translator.locale + '.json');
let fallback = {};

if (Translator.locale !== Translator.fallback) {
    fallback = require('~/translations/' + Translator.fallback + '.json');
} else {
    fallback = { translations: { [Translator.fallback]: {} } };
}

Translator.fromJSON({ translations: { ...messages.translations, ...fallback.translations } });

export default Translator;
