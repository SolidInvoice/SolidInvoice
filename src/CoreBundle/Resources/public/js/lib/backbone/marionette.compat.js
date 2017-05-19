(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(require('underscore'), require('marionette'), require('backbone.radio'), require('backbone')) :
        typeof define === 'function' && define.amd ? define(['underscore', 'marionette', 'backbone.radio', 'backbone'], factory) :
            (global.mnV3Compat = factory(global._,global.Marionette,global.Radio,global.Backbone));
}(this, function (_,Marionette,Radio,Backbone) { 'use strict';

    _ = 'default' in _ ? _['default'] : _;
    Marionette = 'default' in Marionette ? Marionette['default'] : Marionette;
    Radio = 'default' in Radio ? Radio['default'] : Radio;
    Backbone = 'default' in Backbone ? Backbone['default'] : Backbone;

    function ApplicationWreqr () {

        function dep() {
            Marionette.deprecate('Global channels are deprecated.  Create your own Radio channels.');
        }

        var originalConstructor = Marionette.Application.prototype.constructor;

        Marionette.Application = Marionette.Application.extend({
            constructor: function constructor() {
                this._initV2Channel();
                originalConstructor.apply(this, arguments);
            },

            // Command execution, facilitated by Backbone.Wreqr.Commands
            execute: function execute() {
                this.commands.execute.apply(this.commands, arguments);
            },

            // Request/response, facilitated by Backbone.Wreqr.RequestResponse
            request: function request() {
                return this.reqres.request.apply(this.reqres, arguments);
            },

            _initV2Channel: function _initV2Channel() {
                this.channelName = _.result(this, 'channelName') || 'global';
                this.channel = _.result(this, 'channel') || Radio.channel(this.channelName);
                this.channel.__deprecateChannel = true;
                this.vent = this.channel;
                this.reqres = this.channel;
                this.commands = this.channel;

                var channelOn = this.channel.on;

                this.channel.on = function () {
                    dep();
                    return channelOn.apply(this, arguments);
                };

                var channelRequest = this.channel.request;

                this.channel.request = function () {
                    dep();
                    return channelRequest.apply(this, arguments);
                };

                this.channel.execute = function () {
                    dep();
                    Marionette.deprecate('Channel commands are deprecated.  Use requests.');
                    return channelRequest.apply(this, arguments);
                };

                var listenTo = Marionette.Object.listenTo;

                Marionette.Object.listenTo = function (obj) {
                    if (obj.__deprecateChannel) {
                        dep();
                    }
                    listenTo.apply(this, arguments);
                };
            }
        });
    }

    function bindEntityEvents () {

        var originalBind = Marionette.bindEvents;
        var originalUnbind = Marionette.unbindEvents;

        Marionette.bindEvents = function (context, entity, bindings) {
            if (_.isFunction(bindings)) {
                Marionette.deprecate('bindEvents no longer accepts bindings as a function in v3');
                bindings = bindings.call(context);
            }
            return originalBind(context, entity, bindings);
        };

        Marionette.unbindEvents = function (context, entity, bindings) {
            if (_.isFunction(bindings)) {
                Marionette.deprecate('unbindEvents no longer accepts bindings as a function in v3');
                bindings = bindings.call(context);
            }
            return originalUnbind(context, entity, bindings);
        };

        Marionette.bindEntityEvents = function (context, entity, bindings) {
            Marionette.deprecate('bindEntityEvents has been renamed to bindEvents in v3.');
            return Marionette.bindEvents(context, entity, bindings);
        };

        Marionette.unbindEntityEvents = function (context, entity, bindings) {
            Marionette.deprecate('unbindEntityEvents renamed to unbindEvents in v3.');
            return Marionette.unbindEvents(context, entity, bindings);
        };

        var bindEventsMixin = {
            bindEntityEvents: function bindEntityEvents() {
                Marionette.deprecate('bindEntityEvents has been renamed to bindEvents in v3.');

                for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                    args[_key] = arguments[_key];
                }

                return Marionette.bindEvents.apply(Marionette, [this].concat(args));
            },
            unbindEntityEvents: function unbindEntityEvents() {
                Marionette.deprecate('unbindEntityEvents renamed to unbindEvents in v3.');

                for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
                    args[_key2] = arguments[_key2];
                }

                return Marionette.unbindEvents.apply(Marionette, [this].concat(args));
            }
        };

        _.extend(Marionette.Object.prototype, bindEventsMixin);

        _.extend(Marionette.View.prototype, bindEventsMixin);

        _.extend(Marionette.CollectionView.prototype, bindEventsMixin);
    }

    function childEvents () {

        // Cache `childViewEvents` and `childViewTriggers`
        function _buildEventProxies() {
            if (this.childEvents || this.options.childEvents) {
                Marionette.deprecate('childEvents are deprecated. Use childViewEvents');
                this.mergeOptions(this.options, ['childEvents']);
                this._childViewEvents = _.result(this, 'childEvents');
            } else {
                this._childViewEvents = _.result(this, 'childViewEvents');
            }

            this._childViewTriggers = _.result(this, 'childViewTriggers');
        }

        _.extend(Marionette.View.prototype, {
            _buildEventProxies: _buildEventProxies
        });

        _.extend(Marionette.CollectionView.prototype, {
            _buildEventProxies: _buildEventProxies
        });
    }

    function restoreFunction (privateFunction, publicFunction, deprecation, ClassName) {
        var original = Marionette[ClassName].prototype[privateFunction];
        var options = {};

        options[privateFunction] = function () {
            this._nodep = true;
            return this[publicFunction].apply(this, arguments);
        };

        options[publicFunction] = function () {
            if (this._nodep) {
                this._nodep = false;
            } else {
                Marionette.deprecate(deprecation);
            }
            return original.apply(this, arguments);
        };

        _.extend(Marionette[ClassName].prototype, options);
    }

    function CollectionView () {

        restoreFunction('_endBuffering', 'endBuffering', 'endBuffering is now private.', 'CollectionView');
        restoreFunction('_startBuffering', 'startBuffering', 'startBuffering is now private.', 'CollectionView');
        restoreFunction('_showCollection', 'showCollection', 'showCollection is now private.', 'CollectionView');
        restoreFunction('_showEmptyView', 'showEmptyView', 'showEmptyView is now private.', 'CollectionView');
        restoreFunction('_destroyEmptyView', 'destroyEmptyView', 'destroyEmptyView is now private.', 'CollectionView');
        restoreFunction('_checkEmpty', 'checkEmpty', 'checkEmpty is now private.', 'CollectionView');
        restoreFunction('_destroyChildren', 'destroyChildren', 'destroyChildren is now private.', 'CollectionView');
        restoreFunction('_proxyChildEvents', 'proxyChildEvents', 'proxyChildEvents is now private.', 'CollectionView');
        restoreFunction('_addChild', 'addChild', 'addChild is now private.', 'CollectionView');

        var originalConstructor = Marionette.CollectionView.prototype.constructor;

        Marionette.CollectionView = Marionette.CollectionView.extend({
            constructor: function constructor() {
                Backbone.Events.on.call(this, 'render:children', function () {
                    this.triggerMethod('render:collection', this);
                });
                Backbone.Events.on.call(this, 'before:render:children', function () {
                    this.triggerMethod('before:render:collection', this);
                });

                Backbone.Events.on.call(this, 'destroy:children', function () {
                    this.triggerMethod('destroy:collection', this);
                });
                Backbone.Events.on.call(this, 'before:destroy:children', function () {
                    this.triggerMethod('before:destroy:collection', this);
                });

                originalConstructor.apply(this, arguments);
            },
            initRenderBuffer: function initRenderBuffer() {
                Marionette.deprecate('initRenderBuffer is now private.');
                this._bufferedChildren = [];
            }
        });

        var originalConstructorComp = Marionette.CompositeView.prototype.constructor;

        Marionette.CompositeView = Marionette.CompositeView.extend({
            constructor: function constructor() {
                Backbone.Events.on.call(this, 'render:children', function () {
                    this.triggerMethod('render:collection', this);
                });
                Backbone.Events.on.call(this, 'before:render:children', function () {
                    this.triggerMethod('before:render:collection', this);
                });

                Backbone.Events.on.call(this, 'destroy:children', function () {
                    this.triggerMethod('destroy:collection', this);
                });
                Backbone.Events.on.call(this, 'before:destroy:children', function () {
                    this.triggerMethod('before:destroy:collection', this);
                });
                originalConstructorComp.apply(this, arguments);
            }
        });
    }

    function CompositeView () {

        var originalRenderTemp = Marionette.CompositeView.prototype._renderTemplate;

        _.extend(Marionette.CompositeView.prototype, {
            _renderTemplate: function _renderTemplate() {
                this.triggerMethod('before:render:template');
                originalRenderTemp.apply(this, arguments);
                this.triggerMethod('render:template');
            }
        });
    }

    function Controller () {
        Marionette.Controller = Marionette.Object.extend({
            constructor: function constructor(options) {
                this.options = options || {};

                var args = Array.prototype.slice.call(arguments);
                args[0] = this.options;

                Marionette.deprecate('Marionette.Controller is deprecated. Use Marionette.Object');
                Marionette.Object.prototype.constructor.apply(this, args);
            },


            destroy: function destroy() {
                for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                    args[_key] = arguments[_key];
                }

                this.triggerMethod.apply(this, ['before:destroy'].concat(args));
                this.triggerMethod.apply(this, ['destroy'].concat(args));
                this.stopListening();

                return this;
            }
        });
    }

    function deprecatedEvents () {
        var deprecatedEvents = {
            'render:collection': true,
            'destroy:collection:': true,
            'render:template': true
        };

        var dep = function dep(name) {
            Marionette.deprecate(name + ' event is deprecated.');
        };

        var listenTo = Backbone.View.prototype.listenTo;

        Backbone.View.prototype.listenTo = function (obj, name) {
            if (deprecatedEvents[name]) {
                dep(name);
            }
            if (_.isObject(name)) {
                _.each(name, function (value, key) {
                    if (deprecatedEvents[key]) {
                        dep(key);
                    }
                });
            }
            listenTo.apply(this, arguments);
        };

        var on = Backbone.View.prototype.on;

        Backbone.View.prototype.on = function (name) {
            if (deprecatedEvents[name]) {
                dep(name);
            }
            if (_.isObject(name)) {
                _.each(name, function (value, key) {
                    if (deprecatedEvents[key]) {
                        dep(key);
                    }
                });
            }
            on.apply(this, arguments);
        };
    };

    function getChildView () {

        restoreFunction('_getChildView', 'getChildView', 'getChildView is deprecated. Use childView instead.', 'CollectionView');
        restoreFunction('_getEmptyView', 'getEmptyView', 'getEmptyView is deprecated. Use emptyView instead.', 'CollectionView');
    }

    function ItemView () {
        Marionette.ItemView = Marionette.View.extend({
            constructor: function constructor() {
                Marionette.deprecate('Marionette.ItemView is deprecated. Use Marionette.View');
                Marionette.View.prototype.constructor.apply(this, arguments);
            }
        });
    }

    function LayoutView () {
        Marionette.LayoutView = Marionette.View.extend({
            constructor: function constructor() {
                Marionette.deprecate('Marionette.LayoutView is deprecated. Use Marionette.View');
                Marionette.View.prototype.constructor.apply(this, arguments);
            }
        });
    }

    function Module () {

        var originalConstructor = Marionette.Application.prototype.constructor;

        Marionette.Deferred = function () {
            return Backbone.$.Deferred.apply(this, arguments);
        };

        Marionette.Application = Marionette.Application.extend({
            constructor: function constructor() {
                this._initCallbacks = new Marionette.Callbacks();
                this.submodules = {};
                originalConstructor.apply(this, arguments);
            },

            addInitializer: function addInitializer(initializer) {
                Marionette.deprecate('Application Initializers are deprecated and removed in v3.');
                this._initCallbacks.add(initializer);
            },

            start: function start(options) {
                this.triggerMethod('before:start', options);
                this._initCallbacks.run(options, this);
                this.triggerMethod('start', options);
            },

            module: function module(moduleNames, moduleDefinition) {

                // Overwrite the module class if the user specifies one
                var ModuleClass = Marionette.Module.getClass(moduleDefinition);

                var args = _.toArray(arguments);
                args.unshift(this);

                // see the Marionette.Module object for more information
                return ModuleClass.create.apply(ModuleClass, args);
            }
        });

        // Callbacks
        // ---------

        // A simple way of managing a collection of callbacks
        // and executing them at a later point in time, using jQuery's
        // `Deferred` object.
        Marionette.Callbacks = function () {
            this._deferred = Marionette.Deferred();
            this._callbacks = [];
        };

        _.extend(Marionette.Callbacks.prototype, {

            // Add a callback to be executed. Callbacks added here are
            // guaranteed to execute, even if they are added after the
            // `run` method is called.
            add: function add(callback, contextOverride) {
                var promise = _.result(this._deferred, 'promise');

                this._callbacks.push({ cb: callback, ctx: contextOverride });

                promise.then(function (args) {
                    if (contextOverride) {
                        args.context = contextOverride;
                    }
                    callback.call(args.context, args.options);
                });
            },

            // Run all registered callbacks with the context specified.
            // Additional callbacks can be added after this has been run
            // and they will still be executed.
            run: function run(options, context) {
                this._deferred.resolve({
                    options: options,
                    context: context
                });
            },

            // Resets the list of callbacks to be run, allowing the same list
            // to be run multiple times - whenever the `run` method is called.
            reset: function reset() {
                var callbacks = this._callbacks;
                this._deferred = Marionette.Deferred();
                this._callbacks = [];

                _.each(callbacks, function (cb) {
                    this.add(cb.cb, cb.ctx);
                }, this);
            }
        });

        // A simple module system, used to create privacy and encapsulation in
        // Marionette applications
        Marionette.Module = function (moduleName, app, options) {
            Marionette.deprecate('Marionette.module is deprecated and removed in v3.');

            this.moduleName = moduleName;
            this.options = _.extend({}, this.options, options);
            // Allow for a user to overide the initialize
            // for a given module instance.
            this.initialize = options.initialize || this.initialize;

            // Set up an internal store for sub-modules.
            this.submodules = {};

            this._setupInitializersAndFinalizers();

            // Set an internal reference to the app
            // within a module.
            this.app = app;

            if (_.isFunction(this.initialize)) {
                this.initialize(moduleName, app, this.options);
            }
        };

        Marionette.Module.extend = Marionette.extend;

        // Extend the Module prototype with events / listenTo, so that the module
        // can be used as an event aggregator or pub/sub.
        _.extend(Marionette.Module.prototype, Backbone.Events, {

            // By default modules start with their parents.
            startWithParent: true,

            // Initialize is an empty function by default. Override it with your own
            // initialization logic when extending Marionette.Module.
            initialize: function initialize() {},

            // Initializer for a specific module. Initializers are run when the
            // module's `start` method is called.
            addInitializer: function addInitializer(callback) {
                this._initializerCallbacks.add(callback);
            },

            // Finalizers are run when a module is stopped. They are used to teardown
            // and finalize any variables, references, events and other code that the
            // module had set up.
            addFinalizer: function addFinalizer(callback) {
                this._finalizerCallbacks.add(callback);
            },

            // Start the module, and run all of its initializers
            start: function start(options) {
                // Prevent re-starting a module that is already started
                if (this._isInitialized) {
                    return;
                }

                // start the sub-modules (depth-first hierarchy)
                _.each(this.submodules, function (mod) {
                    // check to see if we should start the sub-module with this parent
                    if (mod.startWithParent) {
                        mod.start(options);
                    }
                });

                // run the callbacks to "start" the current module
                this.triggerMethod('before:start', options);

                this._initializerCallbacks.run(options, this);
                this._isInitialized = true;

                this.triggerMethod('start', options);
            },

            // Stop this module by running its finalizers and then stop all of
            // the sub-modules for this module
            stop: function stop() {
                // if we are not initialized, don't bother finalizing
                if (!this._isInitialized) {
                    return;
                }
                this._isInitialized = false;

                this.triggerMethod('before:stop');

                // stop the sub-modules; depth-first, to make sure the
                // sub-modules are stopped / finalized before parents
                _.invoke(this.submodules, 'stop');

                // run the finalizers
                this._finalizerCallbacks.run(undefined, this);

                // reset the initializers and finalizers
                this._initializerCallbacks.reset();
                this._finalizerCallbacks.reset();

                this.triggerMethod('stop');
            },

            // Configure the module with a definition function and any custom args
            // that are to be passed in to the definition function
            addDefinition: function addDefinition(moduleDefinition, customArgs) {
                this._runModuleDefinition(moduleDefinition, customArgs);
            },

            // Internal method: run the module definition function with the correct
            // arguments
            _runModuleDefinition: function _runModuleDefinition(definition, customArgs) {
                // If there is no definition short circut the method.
                if (!definition) {
                    return;
                }

                // build the correct list of arguments for the module definition
                var args = _.flatten([this, this.app, Backbone, Marionette, Backbone.$, _, customArgs]);

                definition.apply(this, args);
            },

            // Internal method: set up new copies of initializers and finalizers.
            // Calling this method will wipe out all existing initializers and
            // finalizers.
            _setupInitializersAndFinalizers: function _setupInitializersAndFinalizers() {
                this._initializerCallbacks = new Marionette.Callbacks();
                this._finalizerCallbacks = new Marionette.Callbacks();
            },

            // import the `triggerMethod` to trigger events with corresponding
            // methods if the method exists
            triggerMethod: function triggerMethod() {
                for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                    args[_key] = arguments[_key];
                }

                return Marionette.triggerMethod.apply(this, [this].concat(args));
            }
        });

        // Class methods to create modules
        _.extend(Marionette.Module, {

            // Create a module, hanging off the app parameter as the parent object.
            create: function create(app, moduleNames, moduleDefinition) {
                var module = app;

                // get the custom args passed in after the module definition and
                // get rid of the module name and definition function
                var customArgs = _.drop(arguments, 3);

                // Split the module names and get the number of submodules.
                // i.e. an example module name of `Doge.Wow.Amaze` would
                // then have the potential for 3 module definitions.
                moduleNames = moduleNames.split('.');
                var length = moduleNames.length;

                // store the module definition for the last module in the chain
                var moduleDefinitions = [];
                moduleDefinitions[length - 1] = moduleDefinition;

                // Loop through all the parts of the module definition
                _.each(moduleNames, function (moduleName, i) {
                    var parentModule = module;
                    module = this._getModule(parentModule, moduleName, app, moduleDefinition);
                    this._addModuleDefinition(parentModule, module, moduleDefinitions[i], customArgs);
                }, this);

                // Return the last module in the definition chain
                return module;
            },

            _getModule: function _getModule(parentModule, moduleName, app, def, args) {
                var options = _.extend({}, def);
                var ModuleClass = this.getClass(def);

                // Get an existing module of this name if we have one
                var module = parentModule[moduleName];

                if (!module) {
                    // Create a new module if we don't have one
                    module = new ModuleClass(moduleName, app, options);
                    parentModule[moduleName] = module;
                    // store the module on the parent
                    parentModule.submodules[moduleName] = module;
                }

                return module;
            },

            // ## Module Classes
            //
            // Module classes can be used as an alternative to the define pattern.
            // The extend function of a Module is identical to the extend functions
            // on other Backbone and Marionette classes.
            // This allows module lifecyle events like `onStart` and `onStop` to be called directly.
            getClass: function getClass(moduleDefinition) {
                var ModuleClass = Marionette.Module;

                if (!moduleDefinition) {
                    return ModuleClass;
                }

                // If all of the module's functionality is defined inside its class,
                // then the class can be passed in directly. `MyApp.module("Foo", FooModule)`.
                if (moduleDefinition.prototype instanceof ModuleClass) {
                    return moduleDefinition;
                }

                return moduleDefinition.moduleClass || ModuleClass;
            },

            // Add the module definition and add a startWithParent initializer function.
            // This is complicated because module definitions are heavily overloaded
            // and support an anonymous function, module class, or options object
            _addModuleDefinition: function _addModuleDefinition(parentModule, module, def, args) {
                var fn = this._getDefine(def);
                var startWithParent = this._getStartWithParent(def, module);

                if (fn) {
                    module.addDefinition(fn, args);
                }

                this._addStartWithParent(parentModule, module, startWithParent);
            },

            _getStartWithParent: function _getStartWithParent(def, module) {
                var swp;

                if (_.isFunction(def) && def.prototype instanceof Marionette.Module) {
                    swp = module.constructor.prototype.startWithParent;
                    return _.isUndefined(swp) ? true : swp;
                }

                if (_.isObject(def)) {
                    swp = def.startWithParent;
                    return _.isUndefined(swp) ? true : swp;
                }

                return true;
            },

            _getDefine: function _getDefine(def) {
                if (_.isFunction(def) && !(def.prototype instanceof Marionette.Module)) {
                    return def;
                }

                if (_.isObject(def)) {
                    return def.define;
                }

                return null;
            },

            _addStartWithParent: function _addStartWithParent(parentModule, module, startWithParent) {
                module.startWithParent = module.startWithParent && startWithParent;

                if (!module.startWithParent || !!module.startWithParentIsConfigured) {
                    return;
                }

                module.startWithParentIsConfigured = true;

                parentModule.addInitializer(function (options) {
                    if (module.startWithParent) {
                        module.start(options);
                    }
                });
            }
        });
    }

    function normalizeUi () {

        var normalizeUIString = function normalizeUIString(uiString, ui) {
            return uiString.replace(/@ui\.[a-zA-Z-_$0-9]*/g, function (r) {
                return ui[r.slice(4)];
            });
        };

        Marionette.normalizeUIString = function (uiString, ui) {
            Marionette.deprecate('normalizeUIString was removed in v3.');
            return normalizeUIString.apply(this, arguments);
        };

        var normalizeUIKeys = function normalizeUIKeys(hash, ui) {
            return _.reduce(hash, function (memo, val, key) {
                var normalizedKey = normalizeUIString(key, ui);
                memo[normalizedKey] = val;
                return memo;
            }, {});
        };

        Marionette.normalizeUIKeys = function (hash, ui) {
            Marionette.deprecate('normalizeUIKeys was removed in v3.');
            return normalizeUIKeys.apply(this, arguments);
        };

        var normalizeUIValues = function normalizeUIValues(hash, ui, properties) {
            _.each(hash, function (val, key) {
                if (_.isString(val)) {
                    hash[key] = normalizeUIString(val, ui);
                } else if (_.isObject(val) && _.isArray(properties)) {
                    _.extend(val, normalizeUIValues(_.pick(val, properties), ui));
                    /* Value is an object, and we got an array of embedded property names to normalize. */
                    _.each(properties, function (property) {
                        var propertyVal = val[property];
                        if (_.isString(propertyVal)) {
                            val[property] = normalizeUIString(propertyVal, ui);
                        }
                    });
                }
            });
            return hash;
        };

        Marionette.normalizeUIValues = function (hash, ui, properties) {
            Marionette.deprecate('normalizeUIValues was removed in v3.');
            return normalizeUIValues.apply(this, arguments);
        };
    }

    function proxyFunctions () {
        Marionette.proxyGetOption = function (optionName) {
            Marionette.deprecate('proxyGetOption has been deprecated and removed in v3.');
            return Marionette.getOption(this, optionName);
        };

        Marionette.proxyBindEntityEvents = function (entity, bindings) {
            Marionette.deprecate('proxyBindEntityEvents has been deprecated and removed in v3.');
            return Marionette.bindEvents(this, entity, bindings);
        };

        Marionette.proxyUnbindEntityEvents = function (entity, bindings) {
            Marionette.deprecate('proxyUnbindEntityEvents has been deprecated and removed in v3.');
            return Marionette.unbindEvents(this, entity, bindings);
        };
    }

    function Region () {

        var originalShow = Marionette.Region.prototype.show;

        Marionette.Region = Marionette.Region.extend({
            attachView: function attachView(view) {
                Marionette.deprecate('Region#attachView was removed in v3. Use Region#show without fear of re-rendering.');
                if (this.currentView) {
                    delete this.currentView._parent;
                }
                view._parent = this;
                this.currentView = view;
                return this;
            },
            show: function show(view, options) {
                if (!this._ensureElement(options)) {
                    return;
                }
                //this._ensureView(view);
                if (view === this.currentView) {
                    return this;
                }
                if (view._isRendered) {
                    view.render();
                    Marionette.deprecate('Rendered views shown in a Region are not re-rendered in v3.');
                }
                var isChangingView = !!this.currentView;
                if (isChangingView) {
                    this.triggerMethod('before:swapOut', this.currentView, this, options);
                    this.triggerMethod('before:swap', view, this, options);
                    this.triggerMethod('swapOut', this.currentView, this, options);
                }
                originalShow.apply(this, arguments);
                if (isChangingView) {
                    this.triggerMethod('swap', view, this, options);
                }
                return this;
            }
        });
    }

    function RegionManager () {

        // Manage one or more related `Marionette.Region` objects.
        Marionette.RegionManager = Marionette.Object.extend({
            constructor: function constructor(options) {
                this._regions = {};
                this.length = 0;

                Marionette.Object.call(this, options);

                this.addRegions(this.getOption('regions'));
            },

            // Add multiple regions using an object literal or a
            // function that returns an object literal, where
            // each key becomes the region name, and each value is
            // the region definition.
            addRegions: function addRegions(regionDefinitions, defaults) {
                regionDefinitions = Marionette._getValue(regionDefinitions, this, arguments);

                if (!_.isEmpty(regionDefinitions)) {
                    Marionette.deprecate('RegionManager is deprecated and removed in v3.');
                }

                return _.reduce(regionDefinitions, function (regions, definition, name) {
                    if (_.isString(definition)) {
                        definition = { selector: definition };
                    }
                    if (definition.selector) {
                        definition = _.defaults({}, definition, defaults);
                    }

                    regions[name] = this.addRegion(name, definition);
                    return regions;
                }, {}, this);
            },

            // Add an individual region to the region manager,
            // and return the region instance
            addRegion: function addRegion(name, definition) {
                var region;

                if (definition instanceof Marionette.Region) {
                    region = definition;
                } else {
                    region = Marionette.Region.buildRegion(definition, Marionette.Region);
                }

                this.triggerMethod('before:add:region', name, region);

                region._parent = this;
                this._store(name, region);

                this.triggerMethod('add:region', name, region);
                return region;
            },

            // Get a region by name
            get: function get(name) {
                return this._regions[name];
            },

            // Gets all the regions contained within
            // the `regionManager` instance.
            getRegions: function getRegions() {
                return _.clone(this._regions);
            },

            // Remove a region by name
            removeRegion: function removeRegion(name) {
                var region = this._regions[name];
                this._remove(name, region);

                return region;
            },

            // Empty all regions in the region manager, and
            // remove them
            removeRegions: function removeRegions() {
                var regions = this.getRegions();
                _.each(this._regions, function (region, name) {
                    this._remove(name, region);
                }, this);

                return regions;
            },

            // Empty all regions in the region manager, but
            // leave them attached
            emptyRegions: function emptyRegions() {
                var regions = this.getRegions();
                _.invoke(regions, 'empty');
                return regions;
            },

            // Destroy all regions and shut down the region
            // manager entirely
            destroy: function destroy() {
                this.removeRegions();
                return Marionette.Object.prototype.destroy.apply(this, arguments);
            },

            // internal method to store regions
            _store: function _store(name, region) {
                if (!this._regions[name]) {
                    this.length++;
                }

                this._regions[name] = region;
            },

            // internal method to remove a region
            _remove: function _remove(name, region) {
                this.triggerMethod('before:remove:region', name, region);
                region.empty();
                region.stopListening();

                delete region._parent;
                delete this._regions[name];
                this.length--;
                this.triggerMethod('remove:region', name, region);
            }
        });

        // Mix in methods from Underscore, for iteration, and other
        // collection related features.
        // Borrowing this code from Backbone.Collection:
        // http://backbonejs.org/docs/backbone.html#section-121
        var _actAsCollection = function _actAsCollection(object, listProperty) {
            var methods = ['forEach', 'each', 'map', 'find', 'detect', 'filter', 'select', 'reject', 'every', 'all', 'some', 'any', 'include', 'contains', 'invoke', 'toArray', 'first', 'initial', 'rest', 'last', 'without', 'isEmpty', 'pluck'];

            _.each(methods, function (method) {
                object[method] = function () {
                    var list = _.values(_.result(this, listProperty));
                    var args = [list].concat(_.toArray(arguments));
                    return _[method].apply(_, args);
                };
            });
        };

        Marionette.actAsCollection = function (object, listProperty) {
            Marionette.deprecate('actAsCollection is deprecated and removed in v3.');

            return _actAsCollection.apply(this, arguments);
        };

        _actAsCollection(Marionette.RegionManager.prototype, '_regions');
    }

    function RegionShowEvent () {

        function dep() {
            Marionette.deprecate('Show events are no longer triggered on the View.  User render or attach.');
        }

        function triggerOnChildren(children, name) {
            if (!children) {
                return;
            }

            children.each(function (v) {
                if (!v._isShown) {
                    Marionette.triggerMethodOn(v, name, v);
                }
                if (name === 'show') {
                    v._isShown = true;
                }
            });
        }

        var regionTriggerMethod = Marionette.Region.prototype.triggerMethod;

        Marionette.Region.prototype.triggerMethod = function (name, region, view, options) {
            var result;

            if (name === 'before:show') {
                result = regionTriggerMethod.call(this, 'before:show', view, region, options);
                if (!view._isShown) {
                    view.once('render', function () {
                        Marionette.triggerMethodOn(view, 'before:show', view, region, options);
                    });
                }
                view.once('render', function () {
                    triggerOnChildren(view.children, name);
                });
            } else if (name === 'show') {
                result = regionTriggerMethod.call(this, 'show', view, region, options);
                if (!view._isShown) {
                    Marionette.triggerMethodOn(view, 'show', view, region, options);
                }
                view._isShown = true;
                triggerOnChildren(view.children, name);
            } else {
                result = regionTriggerMethod.apply(this, arguments);
            }

            return result;
        };

        var _addChildView = Marionette.CollectionView.prototype._addChildView;

        Marionette.CollectionView.prototype._addChildView = function (view) {
            view.once('render', function () {
                // trigger the 'before:show' event on `view` if the collection view has already been shown
                if (this._isShown && !this._isBuffering) {
                    Marionette.triggerMethodOn(view, 'before:show', view);
                }
            }, this);

            _addChildView.apply(this, arguments);

            if (this._isShown && !this._isBuffering) {
                if (!view._isShown) {
                    Marionette.triggerMethodOn(view, 'show', view);
                }
                view._isShown = true;
            }
        };

        // split the event name on the ":"
        var splitter = /(^|:)(\w)/gi;

        // take the event section ("section1:section2:section3")
        // and turn it in to uppercase name onSection1Section2Section3
        function getEventName(match, prefix, eventName) {
            return eventName.toUpperCase();
        }

        var trigger = Backbone.Events.trigger;

        Backbone.Events.trigger = function (name) {
            var isView = this.prototype instanceof Backbone.View || this === Backbone.View;
            var isRegion = this.prototype instanceof Marionette.Region || this === Marionette.Region;
            if (isView || isRegion) {
                var methodName = 'on' + name.replace(splitter, getEventName);
                var method = this.options && this.options[methodName] || this[methodName];

                if (_.isFunction(method)) {
                    if (isView) {
                        dep();
                    } else {
                        if (method.length > 1) {
                            Marionette.deprecate('Region show events in v3 pass the region and the 1st argument and the view as the 2nd');
                        }
                    }
                }

                if (!this._events) {
                    return this;
                }

                if ((name === 'before:show' || name === 'show') && this._events[name]) {
                    if (isView) {
                        dep();
                    } else {
                        if (this._events[name].length > 1) {
                            Marionette.deprecate('Region show events in v3 pass the region and the 1st argument and the view as the 2nd');
                        }
                    }
                }
            }

            return trigger.apply(this, arguments);
        };
    }

    function regionsOnApplication () {

        function dep() {
            Marionette.deprecate('Regions attached to the Application are deprecated. Application now only has a single region.');
        }

        var originalConstructor = Marionette.Application.prototype.constructor;

        Marionette.Application = Marionette.Application.extend({
            constructor: function constructor(options) {
                this._initializeRegions(options);
                originalConstructor.apply(this, arguments);
            },

            // Add regions to your app.
            // Accepts a hash of named strings or Region objects
            // addRegions({something: "#someRegion"})
            // addRegions({something: Region.extend({el: "#someRegion"}) });
            addRegions: function addRegions(regions) {
                return this._regionManager.addRegions(regions);
            },

            // Empty all regions in the app, without removing them
            emptyRegions: function emptyRegions() {
                return this._regionManager.emptyRegions();
            },

            // Removes a region from your app, by name
            // Accepts the regions name
            // removeRegion('myRegion')
            removeRegion: function removeRegion(region) {
                return this._regionManager.removeRegion(region);
            },

            // Provides alternative access to regions
            // Accepts the region name
            // getRegion('main')
            getRegion: function getRegion(region) {
                if (arguments.length) {
                    dep();
                    console.trace();
                    return this._regionManager.get(region);
                }

                return this._region;
            },

            // Get all the regions from the region manager
            getRegions: function getRegions() {
                return this._regionManager.getRegions();
            },

            // Enable easy overriding of the default `RegionManager`
            // for customized region interactions and business-specific
            // view logic for better control over single regions.
            getRegionManager: function getRegionManager() {
                return new Marionette.RegionManager();
            },

            // Internal method to initialize the regions that have been defined in a
            // `regions` attribute on the application instance
            _initializeRegions: function _initializeRegions() {
                var options = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

                var regions = _.isFunction(this.regions) ? this.regions(options) : this.regions || {};

                // Enable users to define `regions` in instance options.
                var optionRegions = options.options && options.options.regions || options.regions;

                // Enable region options to be a function
                if (_.isFunction(optionRegions)) {
                    optionRegions = optionRegions.call(this, options);
                }

                // Overwrite current regions with those passed in options
                _.extend(regions, optionRegions);

                this._initRegionManager();

                if (!_.isEmpty(regions)) {
                    dep();

                    this.addRegions(regions);
                }

                return this;
            },

            // Internal method to set up the region manager
            _initRegionManager: function _initRegionManager() {
                this._regionManager = this.getRegionManager();
                this._regionManager._parent = this;

                this.listenTo(this._regionManager, 'before:add:region', function (name, region) {
                    this.triggerMethod('before:add:region', name, region);
                });

                this.listenTo(this._regionManager, 'add:region', function (name, region) {
                    this[name] = region;
                    this.triggerMethod('add:region', name, region);
                });

                this.listenTo(this._regionManager, 'before:remove:region', function (name, region) {
                    this.triggerMethod('before:remove:region', name, region);
                });

                this.listenTo(this._regionManager, 'remove:region', function (name, region) {
                    delete this[name];
                    this.triggerMethod('remove:region', name, region);
                });
            }
        });
    }

    function regionsOnView () {

        function dep() {
            Marionette.deprecate('Regions attached to the view are deprecated. Use View#getRegion or View#showChildView');
        }

        function _addRegion(view, name, region) {
            var regionShow = region.show;
            var regionEmpty = region.empty;
            var regionReset = region.reset;
            var regionOn = region.on;

            var newRegion = _.extend({}, region, {
                on: function on() {
                    dep();
                    regionOn.apply(this, arguments);
                },
                show: function show() {
                    dep();
                    regionShow.apply(this, arguments);
                },
                empty: function empty() {
                    dep();
                    regionEmpty.apply(this, arguments);
                },
                reset: function reset() {
                    dep();
                    regionReset.apply(this, arguments);
                },

                __deprecatedRegion: true
            });

            view[name] = newRegion;
        }

        function _removeRegion(view, name, region) {
            delete view[name];
        }

        var listenTo = Backbone.View.prototype.listenTo;

        Backbone.View.prototype.listenTo = function (obj) {
            if (obj.__deprecatedRegion) {
                dep();
            }
            listenTo.apply(this, arguments);
        };

        var initRegions = Marionette.View.prototype._initRegions;

        _.extend(Marionette.View.prototype, {
            _initRegions: function _initRegions() {
                this.regionClass = Marionette.Region;
                this.on({
                    'add:region': _addRegion,
                    'remove:region': _removeRegion
                });
                initRegions.apply(this, arguments);
            }
        });
    }

    function RegionStaticMethods () {

        _.extend(Marionette.Region, {

            buildRegion: function buildRegion(regionConfig, DefaultRegionClass) {

                Marionette.deprecate('Region Static Options are deprecated and removed in v3.');

                if (_.isString(regionConfig)) {
                    return this._buildRegionFromSelector(regionConfig, DefaultRegionClass);
                }

                if (regionConfig.selector || regionConfig.el || regionConfig.regionClass) {
                    return this._buildRegionFromObject(regionConfig, DefaultRegionClass);
                }

                if (_.isFunction(regionConfig)) {
                    return this._buildRegionFromRegionClass(regionConfig);
                }

                throw new Marionette.Error({
                    message: 'Improper region configuration type.',
                    url: 'marionette.region.html#region-configuration-types'
                });
            },

            // Build the region from a string selector like '#foo-region'
            _buildRegionFromSelector: function _buildRegionFromSelector(selector, DefaultRegionClass) {
                return new DefaultRegionClass({ el: selector });
            },

            // Build the region from a configuration object
            // ```js
            // { selector: '#foo', regionClass: FooRegion, allowMissingEl: false }
            // ```
            _buildRegionFromObject: function _buildRegionFromObject(regionConfig, DefaultRegionClass) {
                var RegionClass = regionConfig.regionClass || DefaultRegionClass;
                var options = _.omit(regionConfig, 'selector', 'regionClass');

                if (regionConfig.selector && !options.el) {
                    options.el = regionConfig.selector;
                }

                return new RegionClass(options);
            },

            // Build the region directly from a given `RegionClass`
            _buildRegionFromRegionClass: function _buildRegionFromRegionClass(RegionClass) {
                return new RegionClass();
            }
        });
    }

    function templateHelpers () {
        function mixinTemplateContext() {
            var target = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

            if (this.templateHelpers || this.options.templateHelpers) {
                Marionette.deprecate('templateHelpers are deprecated. Use templateContext');
                this.mergeOptions(this.options, ['templateHelpers']);
                var templateHelpers = _.result(this, 'templateHelpers');
                return _.extend(target, templateHelpers);
            }

            var templateContext = _.result(this, 'templateContext');
            return _.extend(target, templateContext);
        }

        function mixinTemplateHelpers() {
            Marionette.deprecate('mixinTemplateHelpers was renamed mixinTemplateContext in v3.');
            mixinTemplateContext.apply(this, arguments);
        }

        _.extend(Marionette.View.prototype, {
            mixinTemplateContext: mixinTemplateContext,
            mixinTemplateHelpers: mixinTemplateHelpers
        });

        _.extend(Marionette.CompositeView.prototype, {
            mixinTemplateContext: mixinTemplateContext,
            mixinTemplateHelpers: mixinTemplateHelpers
        });
    }

    function triggerProxy () {

        // split the event name on the ":"
        var splitter = /(^|:)(\w)/gi;

        // take the event section ("section1:section2:section3")
        // and turn it in to uppercase name onSection1Section2Section3
        function getEventName(match, prefix, eventName) {
            return eventName.toUpperCase();
        }

        var triggerParent = Marionette.View.prototype._triggerEventOnParentLayout;

        function _triggerEventOnParentLayout(eventName) {
            for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
                args[_key - 1] = arguments[_key];
            }

            var layoutView = this._parentView();
            if (!layoutView) {
                return;
            }

            args = args || [];

            var eventPrefix = _.result(layoutView, 'childViewEventPrefix');
            var prefixedEventName = eventPrefix + ':' + eventName;

            var methodName = 'on' + prefixedEventName.replace(splitter, getEventName);
            var method = layoutView.options && layoutView.options[methodName] || layoutView[methodName];

            // If this is true we expect that it is expecting `this`
            // as its first argument
            if ((_.isFunction(method) && method.length) === args.length + 1 || (layoutView._events && layoutView._events[prefixedEventName] && layoutView._events[prefixedEventName].length) === args.length + 1) {
                args = [this].concat(args);
                Marionette.deprecate('The triggering view is no longer prepended on to the arguments of proxied child events.');
            }

            return triggerParent.apply(this, [eventName].concat(args));
        }

        function _proxyChildEvents(view) {
            var prefix = this.getOption('childViewEventPrefix');

            // Forward all child view events through the parent,
            // prepending "childview:" to the event name
            this.listenTo(view, 'all', function () {
                var args = _.toArray(arguments);
                var rootEvent = args[0];

                var childViewEvents = this.normalizeMethods(this._childViewEvents);

                // call collectionView childViewEvent if defined
                if (typeof childViewEvents !== 'undefined' && _.isFunction(childViewEvents[rootEvent])) {
                    childViewEvents[rootEvent].apply(this, [view].concat(_.rest(args)));
                }

                // use the parent view's proxyEvent handlers
                var childViewTriggers = this._childViewTriggers;

                // Call the event with the proxy name on the parent layout
                if (childViewTriggers && _.isString(childViewTriggers[rootEvent])) {
                    this.triggerMethod.apply(this, [childViewTriggers[rootEvent]].concat(args));
                }

                args[0] = prefix + ':' + rootEvent;
                args.splice(1, 0, view);

                this.triggerMethod.apply(this, args);
            });
        }

        _.extend(Marionette.View.prototype, {
            _triggerEventOnParentLayout: _triggerEventOnParentLayout
        });

        _.extend(Marionette.CompositeView.prototype, {
            _triggerEventOnParentLayout: _triggerEventOnParentLayout
        });

        _.extend(Marionette.CollectionView.prototype, {
            _proxyChildEvents: _proxyChildEvents
        });
    }

    function viewOptions () {

        var originalConstructor = Marionette.View.prototype.constructor;

        Marionette.View = Marionette.View.extend({
            constructor: function constructor(options) {
                var args = Array.prototype.slice.call(arguments);

                if (_.isFunction(options)) {
                    Marionette.deprecate('Marionette.View options is no longer supported as a function. Please use an object instead.');
                    options = options();

                    args[0] = options;
                }

                originalConstructor.apply(this, args);
            }
        });
    };

    Marionette.DEV_MODE = true;

    if (!Marionette || Marionette.VERSION.charAt(0) !== '3') {
        alert('marionette-v3-compat patches Marionette v3 to act like v2. Marionette v3 not found.');
    }

    Marionette.VERSION = 'marionette-v3-compat';

    // Add a console.trace to the deprecate message
    Marionette.deprecate._warn = function () {
        var warn = Marionette.deprecate._console.warn || Marionette.deprecate._console.log || function () {};
        console.trace();
        return warn.apply(Marionette.deprecate._console, arguments);
    };

    Marionette._getValue = function (value, context, params) {
        if (_.isFunction(value)) {
            value = params ? value.apply(context, params) : value.call(context);
        }
        return value;
    };

    function marionetteV3Compat () {
        var opts = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

        var patches = _.extend({
            ApplicationWreqr: ApplicationWreqr,
            bindEntityEvents: bindEntityEvents,
            childEvents: childEvents,
            CollectionView: CollectionView,
            CompositeView: CompositeView,
            Controller: Controller,
            deprecatedEvents: deprecatedEvents,
            getChildView: getChildView,
            ItemView: ItemView,
            LayoutView: LayoutView,
            Module: Module,
            normalizeUi: normalizeUi,
            proxyFunctions: proxyFunctions,
            Region: Region,
            RegionManager: RegionManager,
            RegionShowEvent: RegionShowEvent,
            regionsOnApplication: regionsOnApplication,
            regionsOnView: regionsOnView,
            RegionStaticMethods: RegionStaticMethods,
            templateHelpers: templateHelpers,
            triggerProxy: triggerProxy,
            viewOptions: viewOptions
        }, opts);

        _.each(patches, function (patch) {
            if (_.isFunction(patch)) {
                patch();
            }
        });
    }

    return marionetteV3Compat;

}));