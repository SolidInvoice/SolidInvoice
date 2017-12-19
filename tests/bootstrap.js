require('browser-env')();

const jsdom = require('jsdom/lib/old-api.js').jsdom;

global.document = jsdom('<!DOCTYPE html><html><head><meta name="app_config" content="{&quot;accounting&quot;:{&quot;currency&quot;:{&quot;symbol&quot;:&quot;$&quot;,&quot;format&quot;:&quot;%s%v&quot;,&quot;decimal&quot;:&quot;.&quot;,&quot;thousand&quot;:&quot;,&quot;,&quot;precision&quot;:2},&quot;number&quot;:{&quot;precision&quot;:0,&quot;thousand&quot;:&quot;,&quot;,&quot;decimal&quot;:&quot;.&quot;}}}"></head></html>');
global.window = document.defaultView;
window.console = global.console;

Object.keys(document.defaultView).forEach((property) => {
    if (typeof global[property] === 'undefined') {
        global[property] = document.defaultView[property];
    }
});

global.navigator = {
    userAgent: 'node.js'
};