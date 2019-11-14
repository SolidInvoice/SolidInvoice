import Translator from 'translator';

export default function(message, context) {
    return Translator.trans(message, context.hash);
};
