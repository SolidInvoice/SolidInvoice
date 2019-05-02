define(['marionette'], function (Mn) {
    'use strict';

    return Mn.MnObject.extend({
        constructor: function (options, App) {
            if (!(App instanceof Mn.Application)) {
                throw 'Module constructor needs an instance of Marionette.Application as the second argument';
            }

            this.setApp(App);

            Mn.MnObject.call(this, options);
        },
        regions: {},

        /**
         * @property {App} App
         */
        app : null,

        /**
         * @param {App} App
         */
        setApp: function (App) {
            this.app = App;
        }
    });
});