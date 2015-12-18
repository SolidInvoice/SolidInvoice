define(['marionette'], function (Mn) {
    'use strict';

    return Mn.Object.extend({
        constructor: function (options, App) {

            if (!(App instanceof Mn.Application)) {
                throw 'Module constructor needs an instance of Marionette.Application as the second aegument';
            }

            this.setApp(App);

            Mn.Object.call(this, options);
        },
        regions: {},

        /**
         * @property {Marionette.Application} App
         */
        app : null,

        /**
         * @param {Marionette.Application} App
         */
        setApp: function (App) {
            this.app = App;
        }
    });
});