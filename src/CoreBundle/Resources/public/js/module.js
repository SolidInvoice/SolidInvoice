import { Application, MnObject } from 'backbone.marionette';

export default MnObject.extend({
    regions: {},

    /**
     * @property {App} App
     */
    app: null,

    constructor (options, App) {
        if (!( App instanceof Application )) {
            throw 'Module constructor needs an instance of Marionette.Application as the second argument';
        }

        this.app = App;

        MnObject.call(this, options);
    }
});
