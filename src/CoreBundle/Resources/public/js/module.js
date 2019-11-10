import { Application, MnObject } from 'backbone.marionette';

export default MnObject.extend({
    constructor: function(options, App) {
        if (!( App instanceof Application )) {
            throw 'Module constructor needs an instance of Marionette.Application as the second argument';
        }

        this.setApp(App);

        MnObject.call(this, options);
    },
    regions: {},

    /**
     * @property {App} App
     */
    app: null,

    /**
     * @param {App} App
     */
    setApp: function(App) {
        this.app = App;
    }
});
