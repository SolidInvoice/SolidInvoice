import { startStimulusApp } from '@symfony/stimulus-bridge';
import '@symfony/autoimport';
import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';

document.getElementsByTagName('body')[0].addEventListener('swup:connect', (event) => {
    event.detail.swup.on('contentReplaced', () => {
        App(Module);
    });
});

startStimulusApp(require.context('./controllers', true, /\.(j|t)sx?$/));

App(Module);
