<?php

// This file is auto-generated and is for apps only. Bundles SHOULD NOT rely on its content.

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Config\Loader\ParamConfigurator as Param;

/**
 * This class provides array-shapes for configuring the services and bundles of an application.
 *
 * Services declared with the config() method below are autowired and autoconfigured by default.
 *
 * This is for apps only. Bundles SHOULD NOT use it.
 *
 * Example:
 *
 *     ```php
 *     // config/services.php
 *     namespace Symfony\Component\DependencyInjection\Loader\Configurator;
 *
 *     return App::config([
 *         'services' => [
 *             'App\\' => [
 *                 'resource' => '../src/',
 *             ],
 *         ],
 *     ]);
 *     ```
 *
 * @psalm-type ImportsConfig = list<string|array{
 *     resource: string,
 *     type?: string|null,
 *     ignore_errors?: bool,
 * }>
 * @psalm-type ParametersConfig = array<string, scalar|\UnitEnum|array<scalar|\UnitEnum|array<mixed>|Param|null>|Param|null>
 * @psalm-type ArgumentsType = list<mixed>|array<string, mixed>
 * @psalm-type CallType = array<string, ArgumentsType>|array{0:string, 1?:ArgumentsType, 2?:bool}|array{method:string, arguments?:ArgumentsType, returns_clone?:bool}
 * @psalm-type TagsType = list<string|array<string, array<string, mixed>>> // arrays inside the list must have only one element, with the tag name as the key
 * @psalm-type CallbackType = string|array{0:string|ReferenceConfigurator,1:string}|\Closure|ReferenceConfigurator|ExpressionConfigurator
 * @psalm-type DeprecationType = array{package: string, version: string, message?: string}
 * @psalm-type DefaultsType = array{
 *     public?: bool,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 * }
 * @psalm-type InstanceofType = array{
 *     shared?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     properties?: array<string, mixed>,
 *     configurator?: CallbackType,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 * }
 * @psalm-type DefinitionType = array{
 *     class?: string,
 *     file?: string,
 *     parent?: string,
 *     shared?: bool,
 *     synthetic?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     abstract?: bool,
 *     deprecated?: DeprecationType,
 *     factory?: CallbackType,
 *     configurator?: CallbackType,
 *     arguments?: ArgumentsType,
 *     properties?: array<string, mixed>,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     decorates?: string,
 *     decoration_inner_name?: string,
 *     decoration_priority?: int,
 *     decoration_on_invalid?: 'exception'|'ignore'|null,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 *     from_callable?: CallbackType,
 * }
 * @psalm-type AliasType = string|array{
 *     alias: string,
 *     public?: bool,
 *     deprecated?: DeprecationType,
 * }
 * @psalm-type PrototypeType = array{
 *     resource: string,
 *     namespace?: string,
 *     exclude?: string|list<string>,
 *     parent?: string,
 *     shared?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     abstract?: bool,
 *     deprecated?: DeprecationType,
 *     factory?: CallbackType,
 *     arguments?: ArgumentsType,
 *     properties?: array<string, mixed>,
 *     configurator?: CallbackType,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 * }
 * @psalm-type StackType = array{
 *     stack: list<DefinitionType|AliasType|PrototypeType|array<class-string, ArgumentsType|null>>,
 *     public?: bool,
 *     deprecated?: DeprecationType,
 * }
 * @psalm-type ServicesConfig = array{
 *     _defaults?: DefaultsType,
 *     _instanceof?: InstanceofType,
 *     ...<string, DefinitionType|AliasType|PrototypeType|StackType|ArgumentsType|null>
 * }
 * @psalm-type ExtensionType = array<string, mixed>
 * @psalm-type FrameworkConfig = array{
 *     secret?: scalar|Param|null,
 *     http_method_override?: bool|Param, // Set true to enable support for the '_method' request parameter to determine the intended HTTP method on POST requests. // Default: false
 *     allowed_http_method_override?: null|list<string|Param>,
 *     trust_x_sendfile_type_header?: scalar|Param|null, // Set true to enable support for xsendfile in binary file responses. // Default: "%env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)%"
 *     ide?: scalar|Param|null, // Default: "%env(default::SYMFONY_IDE)%"
 *     test?: bool|Param,
 *     default_locale?: scalar|Param|null, // Default: "en"
 *     set_locale_from_accept_language?: bool|Param, // Whether to use the Accept-Language HTTP header to set the Request locale (only when the "_locale" request attribute is not passed). // Default: false
 *     set_content_language_from_locale?: bool|Param, // Whether to set the Content-Language HTTP header on the Response using the Request locale. // Default: false
 *     enabled_locales?: list<scalar|Param|null>,
 *     trusted_hosts?: string|list<scalar|Param|null>,
 *     trusted_proxies?: mixed, // Default: ["%env(default::SYMFONY_TRUSTED_PROXIES)%"]
 *     trusted_headers?: string|list<scalar|Param|null>,
 *     error_controller?: scalar|Param|null, // Default: "error_controller"
 *     handle_all_throwables?: bool|Param, // HttpKernel will handle all kinds of \Throwable. // Default: true
 *     csrf_protection?: bool|array{
 *         enabled?: scalar|Param|null, // Default: null
 *         stateless_token_ids?: list<scalar|Param|null>,
 *         check_header?: scalar|Param|null, // Whether to check the CSRF token in a header in addition to a cookie when using stateless protection. // Default: false
 *         cookie_name?: scalar|Param|null, // The name of the cookie to use when using stateless protection. // Default: "csrf-token"
 *     },
 *     form?: bool|array{ // Form configuration
 *         enabled?: bool|Param, // Default: true
 *         csrf_protection?: bool|array{
 *             enabled?: scalar|Param|null, // Default: null
 *             token_id?: scalar|Param|null, // Default: null
 *             field_name?: scalar|Param|null, // Default: "_token"
 *             field_attr?: array<string, scalar|Param|null>,
 *         },
 *     },
 *     http_cache?: bool|array{ // HTTP cache configuration
 *         enabled?: bool|Param, // Default: false
 *         debug?: bool|Param, // Default: "%kernel.debug%"
 *         trace_level?: "none"|"short"|"full"|Param,
 *         trace_header?: scalar|Param|null,
 *         default_ttl?: int|Param,
 *         private_headers?: list<scalar|Param|null>,
 *         skip_response_headers?: list<scalar|Param|null>,
 *         allow_reload?: bool|Param,
 *         allow_revalidate?: bool|Param,
 *         stale_while_revalidate?: int|Param,
 *         stale_if_error?: int|Param,
 *         terminate_on_cache_hit?: bool|Param,
 *     },
 *     esi?: bool|array{ // ESI configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     ssi?: bool|array{ // SSI configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     fragments?: bool|array{ // Fragments configuration
 *         enabled?: bool|Param, // Default: false
 *         hinclude_default_template?: scalar|Param|null, // Default: null
 *         path?: scalar|Param|null, // Default: "/_fragment"
 *     },
 *     profiler?: bool|array{ // Profiler configuration
 *         enabled?: bool|Param, // Default: false
 *         collect?: bool|Param, // Default: true
 *         collect_parameter?: scalar|Param|null, // The name of the parameter to use to enable or disable collection on a per request basis. // Default: null
 *         only_exceptions?: bool|Param, // Default: false
 *         only_main_requests?: bool|Param, // Default: false
 *         dsn?: scalar|Param|null, // Default: "file:%kernel.cache_dir%/profiler"
 *         collect_serializer_data?: bool|Param, // Enables the serializer data collector and profiler panel. // Default: false
 *     },
 *     workflows?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         workflows?: array<string, array{ // Default: []
 *             audit_trail?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *             },
 *             type?: "workflow"|"state_machine"|Param, // Default: "state_machine"
 *             marking_store?: array{
 *                 type?: "method"|Param,
 *                 property?: scalar|Param|null,
 *                 service?: scalar|Param|null,
 *             },
 *             supports?: string|list<scalar|Param|null>,
 *             definition_validators?: list<scalar|Param|null>,
 *             support_strategy?: scalar|Param|null,
 *             initial_marking?: \BackedEnum|string|list<scalar|Param|null>,
 *             events_to_dispatch?: null|list<string|Param>,
 *             places?: string|list<array{ // Default: []
 *                 name?: scalar|Param|null,
 *                 metadata?: array<string, mixed>,
 *             }>,
 *             transitions?: list<array{ // Default: []
 *                 name?: string|Param,
 *                 guard?: string|Param, // An expression to block the transition.
 *                 from?: \BackedEnum|string|list<array{ // Default: []
 *                     place?: string|Param,
 *                     weight?: int|Param, // Default: 1
 *                 }>,
 *                 to?: \BackedEnum|string|list<array{ // Default: []
 *                     place?: string|Param,
 *                     weight?: int|Param, // Default: 1
 *                 }>,
 *                 weight?: int|Param, // Default: 1
 *                 metadata?: array<string, mixed>,
 *             }>,
 *             metadata?: array<string, mixed>,
 *         }>,
 *     },
 *     router?: bool|array{ // Router configuration
 *         enabled?: bool|Param, // Default: false
 *         resource?: scalar|Param|null,
 *         type?: scalar|Param|null,
 *         cache_dir?: scalar|Param|null, // Deprecated: Setting the "framework.router.cache_dir.cache_dir" configuration option is deprecated. It will be removed in version 8.0. // Default: "%kernel.build_dir%"
 *         default_uri?: scalar|Param|null, // The default URI used to generate URLs in a non-HTTP context. // Default: null
 *         http_port?: scalar|Param|null, // Default: 80
 *         https_port?: scalar|Param|null, // Default: 443
 *         strict_requirements?: scalar|Param|null, // set to true to throw an exception when a parameter does not match the requirements set to false to disable exceptions when a parameter does not match the requirements (and return null instead) set to null to disable parameter checks against requirements 'true' is the preferred configuration in development mode, while 'false' or 'null' might be preferred in production // Default: true
 *         utf8?: bool|Param, // Default: true
 *     },
 *     session?: bool|array{ // Session configuration
 *         enabled?: bool|Param, // Default: false
 *         storage_factory_id?: scalar|Param|null, // Default: "session.storage.factory.native"
 *         handler_id?: scalar|Param|null, // Defaults to using the native session handler, or to the native *file* session handler if "save_path" is not null.
 *         name?: scalar|Param|null,
 *         cookie_lifetime?: scalar|Param|null,
 *         cookie_path?: scalar|Param|null,
 *         cookie_domain?: scalar|Param|null,
 *         cookie_secure?: true|false|"auto"|Param, // Default: "auto"
 *         cookie_httponly?: bool|Param, // Default: true
 *         cookie_samesite?: null|"lax"|"strict"|"none"|Param, // Default: "lax"
 *         use_cookies?: bool|Param,
 *         gc_divisor?: scalar|Param|null,
 *         gc_probability?: scalar|Param|null,
 *         gc_maxlifetime?: scalar|Param|null,
 *         save_path?: scalar|Param|null, // Defaults to "%kernel.cache_dir%/sessions" if the "handler_id" option is not null.
 *         metadata_update_threshold?: int|Param, // Seconds to wait between 2 session metadata updates. // Default: 0
 *         sid_length?: int|Param, // Deprecated: Setting the "framework.session.sid_length.sid_length" configuration option is deprecated. It will be removed in version 8.0. No alternative is provided as PHP 8.4 has deprecated the related option.
 *         sid_bits_per_character?: int|Param, // Deprecated: Setting the "framework.session.sid_bits_per_character.sid_bits_per_character" configuration option is deprecated. It will be removed in version 8.0. No alternative is provided as PHP 8.4 has deprecated the related option.
 *     },
 *     request?: bool|array{ // Request configuration
 *         enabled?: bool|Param, // Default: false
 *         formats?: array<string, string|list<scalar|Param|null>>,
 *     },
 *     assets?: bool|array{ // Assets configuration
 *         enabled?: bool|Param, // Default: true
 *         strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
 *         version_strategy?: scalar|Param|null, // Default: null
 *         version?: scalar|Param|null, // Default: null
 *         version_format?: scalar|Param|null, // Default: "%%s?%%s"
 *         json_manifest_path?: scalar|Param|null, // Default: null
 *         base_path?: scalar|Param|null, // Default: ""
 *         base_urls?: string|list<scalar|Param|null>,
 *         packages?: array<string, array{ // Default: []
 *             strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
 *             version_strategy?: scalar|Param|null, // Default: null
 *             version?: scalar|Param|null,
 *             version_format?: scalar|Param|null, // Default: null
 *             json_manifest_path?: scalar|Param|null, // Default: null
 *             base_path?: scalar|Param|null, // Default: ""
 *             base_urls?: string|list<scalar|Param|null>,
 *         }>,
 *     },
 *     asset_mapper?: bool|array{ // Asset Mapper configuration
 *         enabled?: bool|Param, // Default: false
 *         paths?: string|array<string, scalar|Param|null>,
 *         excluded_patterns?: list<scalar|Param|null>,
 *         exclude_dotfiles?: bool|Param, // If true, any files starting with "." will be excluded from the asset mapper. // Default: true
 *         server?: bool|Param, // If true, a "dev server" will return the assets from the public directory (true in "debug" mode only by default). // Default: true
 *         public_prefix?: scalar|Param|null, // The public path where the assets will be written to (and served from when "server" is true). // Default: "/assets/"
 *         missing_import_mode?: "strict"|"warn"|"ignore"|Param, // Behavior if an asset cannot be found when imported from JavaScript or CSS files - e.g. "import './non-existent.js'". "strict" means an exception is thrown, "warn" means a warning is logged, "ignore" means the import is left as-is. // Default: "warn"
 *         extensions?: array<string, scalar|Param|null>,
 *         importmap_path?: scalar|Param|null, // The path of the importmap.php file. // Default: "%kernel.project_dir%/importmap.php"
 *         importmap_polyfill?: scalar|Param|null, // The importmap name that will be used to load the polyfill. Set to false to disable. // Default: "es-module-shims"
 *         importmap_script_attributes?: array<string, scalar|Param|null>,
 *         vendor_dir?: scalar|Param|null, // The directory to store JavaScript vendors. // Default: "%kernel.project_dir%/assets/vendor"
 *         precompress?: bool|array{ // Precompress assets with Brotli, Zstandard and gzip.
 *             enabled?: bool|Param, // Default: false
 *             formats?: list<scalar|Param|null>,
 *             extensions?: list<scalar|Param|null>,
 *         },
 *     },
 *     translator?: bool|array{ // Translator configuration
 *         enabled?: bool|Param, // Default: true
 *         fallbacks?: string|list<scalar|Param|null>,
 *         logging?: bool|Param, // Default: false
 *         formatter?: scalar|Param|null, // Default: "translator.formatter.default"
 *         cache_dir?: scalar|Param|null, // Default: "%kernel.cache_dir%/translations"
 *         default_path?: scalar|Param|null, // The default path used to load translations. // Default: "%kernel.project_dir%/translations"
 *         paths?: list<scalar|Param|null>,
 *         pseudo_localization?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             accents?: bool|Param, // Default: true
 *             expansion_factor?: float|Param, // Default: 1.0
 *             brackets?: bool|Param, // Default: true
 *             parse_html?: bool|Param, // Default: false
 *             localizable_html_attributes?: list<scalar|Param|null>,
 *         },
 *         providers?: array<string, array{ // Default: []
 *             dsn?: scalar|Param|null,
 *             domains?: list<scalar|Param|null>,
 *             locales?: list<scalar|Param|null>,
 *         }>,
 *         globals?: array<string, string|array{ // Default: []
 *             value?: mixed,
 *             message?: string|Param,
 *             parameters?: array<string, scalar|Param|null>,
 *             domain?: string|Param,
 *         }>,
 *     },
 *     validation?: bool|array{ // Validation configuration
 *         enabled?: bool|Param, // Default: true
 *         cache?: scalar|Param|null, // Deprecated: Setting the "framework.validation.cache.cache" configuration option is deprecated. It will be removed in version 8.0.
 *         enable_attributes?: bool|Param, // Default: true
 *         static_method?: string|list<scalar|Param|null>,
 *         translation_domain?: scalar|Param|null, // Default: "validators"
 *         email_validation_mode?: "html5"|"html5-allow-no-tld"|"strict"|"loose"|Param, // Default: "html5"
 *         mapping?: array{
 *             paths?: list<scalar|Param|null>,
 *         },
 *         not_compromised_password?: bool|array{
 *             enabled?: bool|Param, // When disabled, compromised passwords will be accepted as valid. // Default: true
 *             endpoint?: scalar|Param|null, // API endpoint for the NotCompromisedPassword Validator. // Default: null
 *         },
 *         disable_translation?: bool|Param, // Default: false
 *         auto_mapping?: array<string, array{ // Default: []
 *             services?: list<scalar|Param|null>,
 *         }>,
 *     },
 *     annotations?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *     },
 *     serializer?: bool|array{ // Serializer configuration
 *         enabled?: bool|Param, // Default: true
 *         enable_attributes?: bool|Param, // Default: true
 *         name_converter?: scalar|Param|null,
 *         circular_reference_handler?: scalar|Param|null,
 *         max_depth_handler?: scalar|Param|null,
 *         mapping?: array{
 *             paths?: list<scalar|Param|null>,
 *         },
 *         default_context?: array<string, mixed>,
 *         named_serializers?: array<string, array{ // Default: []
 *             name_converter?: scalar|Param|null,
 *             default_context?: array<string, mixed>,
 *             include_built_in_normalizers?: bool|Param, // Whether to include the built-in normalizers // Default: true
 *             include_built_in_encoders?: bool|Param, // Whether to include the built-in encoders // Default: true
 *         }>,
 *     },
 *     property_access?: bool|array{ // Property access configuration
 *         enabled?: bool|Param, // Default: true
 *         magic_call?: bool|Param, // Default: false
 *         magic_get?: bool|Param, // Default: true
 *         magic_set?: bool|Param, // Default: true
 *         throw_exception_on_invalid_index?: bool|Param, // Default: false
 *         throw_exception_on_invalid_property_path?: bool|Param, // Default: true
 *     },
 *     type_info?: bool|array{ // Type info configuration
 *         enabled?: bool|Param, // Default: true
 *         aliases?: array<string, scalar|Param|null>,
 *     },
 *     property_info?: bool|array{ // Property info configuration
 *         enabled?: bool|Param, // Default: true
 *         with_constructor_extractor?: bool|Param, // Registers the constructor extractor.
 *     },
 *     cache?: array{ // Cache configuration
 *         prefix_seed?: scalar|Param|null, // Used to namespace cache keys when using several apps with the same shared backend. // Default: "_%kernel.project_dir%.%kernel.container_class%"
 *         app?: scalar|Param|null, // App related cache pools configuration. // Default: "cache.adapter.filesystem"
 *         system?: scalar|Param|null, // System related cache pools configuration. // Default: "cache.adapter.system"
 *         directory?: scalar|Param|null, // Default: "%kernel.share_dir%/pools/app"
 *         default_psr6_provider?: scalar|Param|null,
 *         default_redis_provider?: scalar|Param|null, // Default: "redis://localhost"
 *         default_valkey_provider?: scalar|Param|null, // Default: "valkey://localhost"
 *         default_memcached_provider?: scalar|Param|null, // Default: "memcached://localhost"
 *         default_doctrine_dbal_provider?: scalar|Param|null, // Default: "database_connection"
 *         default_pdo_provider?: scalar|Param|null, // Default: null
 *         pools?: array<string, array{ // Default: []
 *             adapters?: string|list<scalar|Param|null>,
 *             tags?: scalar|Param|null, // Default: null
 *             public?: bool|Param, // Default: false
 *             default_lifetime?: scalar|Param|null, // Default lifetime of the pool.
 *             provider?: scalar|Param|null, // Overwrite the setting from the default provider for this adapter.
 *             early_expiration_message_bus?: scalar|Param|null,
 *             clearer?: scalar|Param|null,
 *         }>,
 *     },
 *     php_errors?: array{ // PHP errors handling configuration
 *         log?: mixed, // Use the application logger instead of the PHP logger for logging PHP errors. // Default: true
 *         throw?: bool|Param, // Throw PHP errors as \ErrorException instances. // Default: true
 *     },
 *     exceptions?: array<string, array{ // Default: []
 *         log_level?: scalar|Param|null, // The level of log message. Null to let Symfony decide. // Default: null
 *         status_code?: scalar|Param|null, // The status code of the response. Null or 0 to let Symfony decide. // Default: null
 *         log_channel?: scalar|Param|null, // The channel of log message. Null to let Symfony decide. // Default: null
 *     }>,
 *     web_link?: bool|array{ // Web links configuration
 *         enabled?: bool|Param, // Default: true
 *     },
 *     lock?: bool|string|array{ // Lock configuration
 *         enabled?: bool|Param, // Default: false
 *         resources?: string|array<string, string|list<scalar|Param|null>>,
 *     },
 *     semaphore?: bool|string|array{ // Semaphore configuration
 *         enabled?: bool|Param, // Default: false
 *         resources?: string|array<string, scalar|Param|null>,
 *     },
 *     messenger?: bool|array{ // Messenger configuration
 *         enabled?: bool|Param, // Default: true
 *         routing?: array<string, string|array{ // Default: []
 *             senders?: list<scalar|Param|null>,
 *         }>,
 *         serializer?: array{
 *             default_serializer?: scalar|Param|null, // Service id to use as the default serializer for the transports. // Default: "messenger.transport.native_php_serializer"
 *             symfony_serializer?: array{
 *                 format?: scalar|Param|null, // Serialization format for the messenger.transport.symfony_serializer service (which is not the serializer used by default). // Default: "json"
 *                 context?: array<string, mixed>,
 *             },
 *         },
 *         transports?: array<string, string|array{ // Default: []
 *             dsn?: scalar|Param|null,
 *             serializer?: scalar|Param|null, // Service id of a custom serializer to use. // Default: null
 *             options?: array<string, mixed>,
 *             failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
 *             retry_strategy?: string|array{
 *                 service?: scalar|Param|null, // Service id to override the retry strategy entirely. // Default: null
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: this delay = (delay * (multiple ^ retries)). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness to apply to the delay (between 0 and 1). // Default: 0.1
 *             },
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use when processing messages. // Default: null
 *         }>,
 *         failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
 *         stop_worker_on_signals?: int|string|list<scalar|Param|null>,
 *         default_bus?: scalar|Param|null, // Default: null
 *         buses?: array<string, array{ // Default: {"messenger.bus.default":{"default_middleware":{"enabled":true,"allow_no_handlers":false,"allow_no_senders":true},"middleware":[]}}
 *             default_middleware?: bool|string|array{
 *                 enabled?: bool|Param, // Default: true
 *                 allow_no_handlers?: bool|Param, // Default: false
 *                 allow_no_senders?: bool|Param, // Default: true
 *             },
 *             middleware?: string|list<string|array{ // Default: []
 *                 id?: scalar|Param|null,
 *                 arguments?: list<mixed>,
 *             }>,
 *         }>,
 *     },
 *     scheduler?: bool|array{ // Scheduler configuration
 *         enabled?: bool|Param, // Default: true
 *     },
 *     disallow_search_engine_index?: bool|Param, // Enabled by default when debug is enabled. // Default: true
 *     http_client?: bool|array{ // HTTP Client configuration
 *         enabled?: bool|Param, // Default: true
 *         max_host_connections?: int|Param, // The maximum number of connections to a single host.
 *         default_options?: array{
 *             headers?: array<string, mixed>,
 *             vars?: array<string, mixed>,
 *             max_redirects?: int|Param, // The maximum number of redirects to follow.
 *             http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
 *             resolve?: array<string, scalar|Param|null>,
 *             proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
 *             no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
 *             timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
 *             max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
 *             bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
 *             verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
 *             verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
 *             cafile?: scalar|Param|null, // A certificate authority file.
 *             capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
 *             local_cert?: scalar|Param|null, // A PEM formatted certificate file.
 *             local_pk?: scalar|Param|null, // A private key file.
 *             passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
 *             ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...)
 *             peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
 *                 sha1?: mixed,
 *                 pin-sha256?: mixed,
 *                 md5?: mixed,
 *             },
 *             crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
 *             extra?: array<string, mixed>,
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
 *             caching?: bool|array{ // Caching configuration.
 *                 enabled?: bool|Param, // Default: false
 *                 cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
 *                 shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
 *                 max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. Null means no cap. // Default: null
 *             },
 *             retry_failed?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
 *                 http_codes?: int|string|array<string, array{ // Default: []
 *                     code?: int|Param,
 *                     methods?: string|list<string|Param>,
 *                 }>,
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay * (multiple ^ retries). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
 *             },
 *         },
 *         mock_response_factory?: scalar|Param|null, // The id of the service that should generate mock responses. It should be either an invokable or an iterable.
 *         scoped_clients?: array<string, string|array{ // Default: []
 *             scope?: scalar|Param|null, // The regular expression that the request URL must match before adding the other options. When none is provided, the base URI is used instead.
 *             base_uri?: scalar|Param|null, // The URI to resolve relative URLs, following rules in RFC 3985, section 2.
 *             auth_basic?: scalar|Param|null, // An HTTP Basic authentication "username:password".
 *             auth_bearer?: scalar|Param|null, // A token enabling HTTP Bearer authorization.
 *             auth_ntlm?: scalar|Param|null, // A "username:password" pair to use Microsoft NTLM authentication (requires the cURL extension).
 *             query?: array<string, scalar|Param|null>,
 *             headers?: array<string, mixed>,
 *             max_redirects?: int|Param, // The maximum number of redirects to follow.
 *             http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
 *             resolve?: array<string, scalar|Param|null>,
 *             proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
 *             no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
 *             timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
 *             max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
 *             bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
 *             verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
 *             verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
 *             cafile?: scalar|Param|null, // A certificate authority file.
 *             capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
 *             local_cert?: scalar|Param|null, // A PEM formatted certificate file.
 *             local_pk?: scalar|Param|null, // A private key file.
 *             passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
 *             ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...).
 *             peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
 *                 sha1?: mixed,
 *                 pin-sha256?: mixed,
 *                 md5?: mixed,
 *             },
 *             crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
 *             extra?: array<string, mixed>,
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
 *             caching?: bool|array{ // Caching configuration.
 *                 enabled?: bool|Param, // Default: false
 *                 cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
 *                 shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
 *                 max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. Null means no cap. // Default: null
 *             },
 *             retry_failed?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
 *                 http_codes?: int|string|array<string, array{ // Default: []
 *                     code?: int|Param,
 *                     methods?: string|list<string|Param>,
 *                 }>,
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay * (multiple ^ retries). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
 *             },
 *         }>,
 *     },
 *     mailer?: bool|array{ // Mailer configuration
 *         enabled?: bool|Param, // Default: true
 *         message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
 *         dsn?: scalar|Param|null, // Default: null
 *         transports?: array<string, scalar|Param|null>,
 *         envelope?: array{ // Mailer Envelope configuration
 *             sender?: scalar|Param|null,
 *             recipients?: string|list<scalar|Param|null>,
 *             allowed_recipients?: string|list<scalar|Param|null>,
 *         },
 *         headers?: array<string, string|array{ // Default: []
 *             value?: mixed,
 *         }>,
 *         dkim_signer?: bool|array{ // DKIM signer configuration
 *             enabled?: bool|Param, // Default: false
 *             key?: scalar|Param|null, // Key content, or path to key (in PEM format with the `file://` prefix) // Default: ""
 *             domain?: scalar|Param|null, // Default: ""
 *             select?: scalar|Param|null, // Default: ""
 *             passphrase?: scalar|Param|null, // The private key passphrase // Default: ""
 *             options?: array<string, mixed>,
 *         },
 *         smime_signer?: bool|array{ // S/MIME signer configuration
 *             enabled?: bool|Param, // Default: false
 *             key?: scalar|Param|null, // Path to key (in PEM format) // Default: ""
 *             certificate?: scalar|Param|null, // Path to certificate (in PEM format without the `file://` prefix) // Default: ""
 *             passphrase?: scalar|Param|null, // The private key passphrase // Default: null
 *             extra_certificates?: scalar|Param|null, // Default: null
 *             sign_options?: int|Param, // Default: null
 *         },
 *         smime_encrypter?: bool|array{ // S/MIME encrypter configuration
 *             enabled?: bool|Param, // Default: false
 *             repository?: scalar|Param|null, // S/MIME certificate repository service. This service shall implement the `Symfony\Component\Mailer\EventListener\SmimeCertificateRepositoryInterface`. // Default: ""
 *             cipher?: int|Param, // A set of algorithms used to encrypt the message // Default: null
 *         },
 *     },
 *     secrets?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         vault_directory?: scalar|Param|null, // Default: "%kernel.project_dir%/config/secrets/%kernel.runtime_environment%"
 *         local_dotenv_file?: scalar|Param|null, // Default: "%kernel.project_dir%/.env.%kernel.environment%.local"
 *         decryption_env_var?: scalar|Param|null, // Default: "base64:default::SYMFONY_DECRYPTION_SECRET"
 *     },
 *     notifier?: bool|array{ // Notifier configuration
 *         enabled?: bool|Param, // Default: true
 *         message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
 *         chatter_transports?: array<string, scalar|Param|null>,
 *         texter_transports?: array<string, scalar|Param|null>,
 *         notification_on_failed_messages?: bool|Param, // Default: false
 *         channel_policy?: array<string, string|list<scalar|Param|null>>,
 *         admin_recipients?: list<array{ // Default: []
 *             email?: scalar|Param|null,
 *             phone?: scalar|Param|null, // Default: ""
 *         }>,
 *     },
 *     rate_limiter?: bool|array{ // Rate limiter configuration
 *         enabled?: bool|Param, // Default: true
 *         limiters?: array<string, array{ // Default: []
 *             lock_factory?: scalar|Param|null, // The service ID of the lock factory used by this limiter (or null to disable locking). // Default: "auto"
 *             cache_pool?: scalar|Param|null, // The cache pool to use for storing the current limiter state. // Default: "cache.rate_limiter"
 *             storage_service?: scalar|Param|null, // The service ID of a custom storage implementation, this precedes any configured "cache_pool". // Default: null
 *             policy?: "fixed_window"|"token_bucket"|"sliding_window"|"compound"|"no_limit"|Param, // The algorithm to be used by this limiter.
 *             limiters?: string|list<scalar|Param|null>,
 *             limit?: int|Param, // The maximum allowed hits in a fixed interval or burst.
 *             interval?: scalar|Param|null, // Configures the fixed interval if "policy" is set to "fixed_window" or "sliding_window". The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
 *             rate?: array{ // Configures the fill rate if "policy" is set to "token_bucket".
 *                 interval?: scalar|Param|null, // Configures the rate interval. The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
 *                 amount?: int|Param, // Amount of tokens to add each interval. // Default: 1
 *             },
 *         }>,
 *     },
 *     uid?: bool|array{ // Uid configuration
 *         enabled?: bool|Param, // Default: true
 *         default_uuid_version?: 7|6|4|1|Param, // Default: 7
 *         name_based_uuid_version?: 5|3|Param, // Default: 5
 *         name_based_uuid_namespace?: scalar|Param|null,
 *         time_based_uuid_version?: 7|6|1|Param, // Default: 7
 *         time_based_uuid_node?: scalar|Param|null,
 *     },
 *     html_sanitizer?: bool|array{ // HtmlSanitizer configuration
 *         enabled?: bool|Param, // Default: true
 *         sanitizers?: array<string, array{ // Default: []
 *             allow_safe_elements?: bool|Param, // Allows "safe" elements and attributes. // Default: false
 *             allow_static_elements?: bool|Param, // Allows all static elements and attributes from the W3C Sanitizer API standard. // Default: false
 *             allow_elements?: array<string, mixed>,
 *             block_elements?: string|list<string|Param>,
 *             drop_elements?: string|list<string|Param>,
 *             allow_attributes?: array<string, mixed>,
 *             drop_attributes?: array<string, mixed>,
 *             force_attributes?: array<string, array<string, string|Param>>,
 *             force_https_urls?: bool|Param, // Transforms URLs using the HTTP scheme to use the HTTPS scheme instead. // Default: false
 *             allowed_link_schemes?: string|list<string|Param>,
 *             allowed_link_hosts?: null|string|list<string|Param>,
 *             allow_relative_links?: bool|Param, // Allows relative URLs to be used in links href attributes. // Default: false
 *             allowed_media_schemes?: string|list<string|Param>,
 *             allowed_media_hosts?: null|string|list<string|Param>,
 *             allow_relative_medias?: bool|Param, // Allows relative URLs to be used in media source attributes (img, audio, video, ...). // Default: false
 *             with_attribute_sanitizers?: string|list<string|Param>,
 *             without_attribute_sanitizers?: string|list<string|Param>,
 *             max_input_length?: int|Param, // The maximum length allowed for the sanitized input. // Default: 0
 *         }>,
 *     },
 *     webhook?: bool|array{ // Webhook configuration
 *         enabled?: bool|Param, // Default: true
 *         message_bus?: scalar|Param|null, // The message bus to use. // Default: "messenger.default_bus"
 *         routing?: array<string, array{ // Default: []
 *             service?: scalar|Param|null,
 *             secret?: scalar|Param|null, // Default: ""
 *         }>,
 *     },
 *     remote-event?: bool|array{ // RemoteEvent configuration
 *         enabled?: bool|Param, // Default: true
 *     },
 *     json_streamer?: bool|array{ // JSON streamer configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 * }
 * @psalm-type SecurityConfig = array{
 *     access_denied_url?: scalar|Param|null, // Default: null
 *     session_fixation_strategy?: "none"|"migrate"|"invalidate"|Param, // Default: "migrate"
 *     hide_user_not_found?: bool|Param, // Deprecated: The "hide_user_not_found" option is deprecated and will be removed in 8.0. Use the "expose_security_errors" option instead.
 *     expose_security_errors?: \Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::None|\Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::AccountStatus|\Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::All|Param, // Default: "none"
 *     erase_credentials?: bool|Param, // Default: true
 *     access_decision_manager?: array{
 *         strategy?: "affirmative"|"consensus"|"unanimous"|"priority"|Param,
 *         service?: scalar|Param|null,
 *         strategy_service?: scalar|Param|null,
 *         allow_if_all_abstain?: bool|Param, // Default: false
 *         allow_if_equal_granted_denied?: bool|Param, // Default: true
 *     },
 *     password_hashers?: array<string, string|array{ // Default: []
 *         algorithm?: scalar|Param|null,
 *         migrate_from?: string|list<scalar|Param|null>,
 *         hash_algorithm?: scalar|Param|null, // Name of hashing algorithm for PBKDF2 (i.e. sha256, sha512, etc..) See hash_algos() for a list of supported algorithms. // Default: "sha512"
 *         key_length?: scalar|Param|null, // Default: 40
 *         ignore_case?: bool|Param, // Default: false
 *         encode_as_base64?: bool|Param, // Default: true
 *         iterations?: scalar|Param|null, // Default: 5000
 *         cost?: int|Param, // Default: null
 *         memory_cost?: scalar|Param|null, // Default: null
 *         time_cost?: scalar|Param|null, // Default: null
 *         id?: scalar|Param|null,
 *     }>,
 *     providers?: array<string, array{ // Default: []
 *         id?: scalar|Param|null,
 *         chain?: array{
 *             providers?: string|list<scalar|Param|null>,
 *         },
 *         memory?: array{
 *             users?: array<string, array{ // Default: []
 *                 password?: scalar|Param|null, // Default: null
 *                 roles?: string|list<scalar|Param|null>,
 *             }>,
 *         },
 *         ldap?: array{
 *             service?: scalar|Param|null,
 *             base_dn?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: null
 *             search_password?: scalar|Param|null, // Default: null
 *             extra_fields?: list<scalar|Param|null>,
 *             default_roles?: string|list<scalar|Param|null>,
 *             role_fetcher?: scalar|Param|null, // Default: null
 *             uid_key?: scalar|Param|null, // Default: "sAMAccountName"
 *             filter?: scalar|Param|null, // Default: "({uid_key}={user_identifier})"
 *             password_attribute?: scalar|Param|null, // Default: null
 *         },
 *         entity?: array{
 *             class?: scalar|Param|null, // The full entity class name of your user class.
 *             property?: scalar|Param|null, // Default: null
 *             manager_name?: scalar|Param|null, // Default: null
 *         },
 *     }>,
 *     firewalls?: array<string, array{ // Default: []
 *         pattern?: scalar|Param|null,
 *         host?: scalar|Param|null,
 *         methods?: string|list<scalar|Param|null>,
 *         security?: bool|Param, // Default: true
 *         user_checker?: scalar|Param|null, // The UserChecker to use when authenticating users in this firewall. // Default: "security.user_checker"
 *         request_matcher?: scalar|Param|null,
 *         access_denied_url?: scalar|Param|null,
 *         access_denied_handler?: scalar|Param|null,
 *         entry_point?: scalar|Param|null, // An enabled authenticator name or a service id that implements "Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface".
 *         provider?: scalar|Param|null,
 *         stateless?: bool|Param, // Default: false
 *         lazy?: bool|Param, // Default: false
 *         context?: scalar|Param|null,
 *         logout?: array{
 *             enable_csrf?: bool|Param|null, // Default: null
 *             csrf_token_id?: scalar|Param|null, // Default: "logout"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_manager?: scalar|Param|null,
 *             path?: scalar|Param|null, // Default: "/logout"
 *             target?: scalar|Param|null, // Default: "/"
 *             invalidate_session?: bool|Param, // Default: true
 *             clear_site_data?: string|list<"*"|"cache"|"cookies"|"storage"|"executionContexts"|Param>,
 *             delete_cookies?: string|array<string, array{ // Default: []
 *                 path?: scalar|Param|null, // Default: null
 *                 domain?: scalar|Param|null, // Default: null
 *                 secure?: scalar|Param|null, // Default: false
 *                 samesite?: scalar|Param|null, // Default: null
 *                 partitioned?: scalar|Param|null, // Default: false
 *             }>,
 *         },
 *         switch_user?: array{
 *             provider?: scalar|Param|null,
 *             parameter?: scalar|Param|null, // Default: "_switch_user"
 *             role?: scalar|Param|null, // Default: "ROLE_ALLOWED_TO_SWITCH"
 *             target_route?: scalar|Param|null, // Default: null
 *         },
 *         required_badges?: list<scalar|Param|null>,
 *         custom_authenticators?: list<scalar|Param|null>,
 *         login_throttling?: array{
 *             limiter?: scalar|Param|null, // A service id implementing "Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface".
 *             max_attempts?: int|Param, // Default: 5
 *             interval?: scalar|Param|null, // Default: "1 minute"
 *             lock_factory?: scalar|Param|null, // The service ID of the lock factory used by the login rate limiter (or null to disable locking). // Default: null
 *             cache_pool?: string|Param, // The cache pool to use for storing the limiter state // Default: "cache.rate_limiter"
 *             storage_service?: string|Param, // The service ID of a custom storage implementation, this precedes any configured "cache_pool" // Default: null
 *         },
 *         two_factor?: array{
 *             check_path?: scalar|Param|null, // Default: "/2fa_check"
 *             post_only?: bool|Param, // Default: true
 *             auth_form_path?: scalar|Param|null, // Default: "/2fa"
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             success_handler?: scalar|Param|null, // Default: null
 *             failure_handler?: scalar|Param|null, // Default: null
 *             authentication_required_handler?: scalar|Param|null, // Default: null
 *             auth_code_parameter_name?: scalar|Param|null, // Default: "_auth_code"
 *             trusted_parameter_name?: scalar|Param|null, // Default: "_trusted"
 *             remember_me_sets_trusted?: scalar|Param|null, // Default: false
 *             multi_factor?: bool|Param, // Default: false
 *             prepare_on_login?: bool|Param, // Default: false
 *             prepare_on_access_denied?: bool|Param, // Default: false
 *             enable_csrf?: scalar|Param|null, // Default: false
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "two_factor"
 *             csrf_header?: scalar|Param|null, // Default: null
 *             csrf_token_manager?: scalar|Param|null, // Default: "scheb_two_factor.csrf_token_manager"
 *             provider?: scalar|Param|null, // Default: null
 *         },
 *         x509?: array{
 *             provider?: scalar|Param|null,
 *             user?: scalar|Param|null, // Default: "SSL_CLIENT_S_DN_Email"
 *             credentials?: scalar|Param|null, // Default: "SSL_CLIENT_S_DN"
 *             user_identifier?: scalar|Param|null, // Default: "emailAddress"
 *         },
 *         remote_user?: array{
 *             provider?: scalar|Param|null,
 *             user?: scalar|Param|null, // Default: "REMOTE_USER"
 *         },
 *         login_link?: array{
 *             check_route?: scalar|Param|null, // Route that will validate the login link - e.g. "app_login_link_verify".
 *             check_post_only?: scalar|Param|null, // If true, only HTTP POST requests to "check_route" will be handled by the authenticator. // Default: false
 *             signature_properties?: list<scalar|Param|null>,
 *             lifetime?: int|Param, // The lifetime of the login link in seconds. // Default: 600
 *             max_uses?: int|Param, // Max number of times a login link can be used - null means unlimited within lifetime. // Default: null
 *             used_link_cache?: scalar|Param|null, // Cache service id used to expired links of max_uses is set.
 *             success_handler?: scalar|Param|null, // A service id that implements Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface.
 *             failure_handler?: scalar|Param|null, // A service id that implements Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface.
 *             provider?: scalar|Param|null, // The user provider to load users from.
 *             secret?: scalar|Param|null, // Default: "%kernel.secret%"
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *         },
 *         form_login?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_parameter?: scalar|Param|null, // Default: "_username"
 *             password_parameter?: scalar|Param|null, // Default: "_password"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "authenticate"
 *             enable_csrf?: bool|Param, // Default: false
 *             post_only?: bool|Param, // Default: true
 *             form_only?: bool|Param, // Default: false
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *         },
 *         form_login_ldap?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_parameter?: scalar|Param|null, // Default: "_username"
 *             password_parameter?: scalar|Param|null, // Default: "_password"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "authenticate"
 *             enable_csrf?: bool|Param, // Default: false
 *             post_only?: bool|Param, // Default: true
 *             form_only?: bool|Param, // Default: false
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         json_login?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_path?: scalar|Param|null, // Default: "username"
 *             password_path?: scalar|Param|null, // Default: "password"
 *         },
 *         json_login_ldap?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_path?: scalar|Param|null, // Default: "username"
 *             password_path?: scalar|Param|null, // Default: "password"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         access_token?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: null
 *             token_extractors?: string|list<scalar|Param|null>,
 *             token_handler?: string|array{
 *                 id?: scalar|Param|null,
 *                 oidc_user_info?: string|array{
 *                     base_uri?: scalar|Param|null, // Base URI of the userinfo endpoint on the OIDC server, or the OIDC server URI to use the discovery (require "discovery" to be configured).
 *                     discovery?: array{ // Enable the OIDC discovery.
 *                         cache?: array{
 *                             id?: scalar|Param|null, // Cache service id to use to cache the OIDC discovery configuration.
 *                         },
 *                     },
 *                     claim?: scalar|Param|null, // Claim which contains the user identifier (e.g. sub, email, etc.). // Default: "sub"
 *                     client?: scalar|Param|null, // HttpClient service id to use to call the OIDC server.
 *                 },
 *                 oidc?: array{
 *                     discovery?: array{ // Enable the OIDC discovery.
 *                         base_uri?: string|list<scalar|Param|null>,
 *                         cache?: array{
 *                             id?: scalar|Param|null, // Cache service id to use to cache the OIDC discovery configuration.
 *                         },
 *                     },
 *                     claim?: scalar|Param|null, // Claim which contains the user identifier (e.g.: sub, email..). // Default: "sub"
 *                     audience?: scalar|Param|null, // Audience set in the token, for validation purpose.
 *                     issuers?: list<scalar|Param|null>,
 *                     algorithm?: array<mixed>,
 *                     algorithms?: list<scalar|Param|null>,
 *                     key?: scalar|Param|null, // Deprecated: The "key" option is deprecated and will be removed in 8.0. Use the "keyset" option instead. // JSON-encoded JWK used to sign the token (must contain a "kty" key).
 *                     keyset?: scalar|Param|null, // JSON-encoded JWKSet used to sign the token (must contain a list of valid public keys).
 *                     encryption?: bool|array{
 *                         enabled?: bool|Param, // Default: false
 *                         enforce?: bool|Param, // When enabled, the token shall be encrypted. // Default: false
 *                         algorithms?: list<scalar|Param|null>,
 *                         keyset?: scalar|Param|null, // JSON-encoded JWKSet used to decrypt the token (must contain a list of valid private keys).
 *                     },
 *                 },
 *                 cas?: array{
 *                     validation_url?: scalar|Param|null, // CAS server validation URL
 *                     prefix?: scalar|Param|null, // CAS prefix // Default: "cas"
 *                     http_client?: scalar|Param|null, // HTTP Client service // Default: null
 *                 },
 *                 oauth2?: scalar|Param|null,
 *             },
 *         },
 *         http_basic?: array{
 *             provider?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: "Secured Area"
 *         },
 *         http_basic_ldap?: array{
 *             provider?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: "Secured Area"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         remember_me?: array{
 *             secret?: scalar|Param|null, // Default: "%kernel.secret%"
 *             service?: scalar|Param|null,
 *             user_providers?: string|list<scalar|Param|null>,
 *             catch_exceptions?: bool|Param, // Default: true
 *             signature_properties?: list<scalar|Param|null>,
 *             token_provider?: string|array{
 *                 service?: scalar|Param|null, // The service ID of a custom remember-me token provider.
 *                 doctrine?: bool|array{
 *                     enabled?: bool|Param, // Default: false
 *                     connection?: scalar|Param|null, // Default: null
 *                 },
 *             },
 *             token_verifier?: scalar|Param|null, // The service ID of a custom rememberme token verifier.
 *             name?: scalar|Param|null, // Default: "REMEMBERME"
 *             lifetime?: int|Param, // Default: 31536000
 *             path?: scalar|Param|null, // Default: "/"
 *             domain?: scalar|Param|null, // Default: null
 *             secure?: true|false|"auto"|Param, // Default: false
 *             httponly?: bool|Param, // Default: true
 *             samesite?: null|"lax"|"strict"|"none"|Param, // Default: null
 *             always_remember_me?: bool|Param, // Default: false
 *             remember_me_parameter?: scalar|Param|null, // Default: "_remember_me"
 *         },
 *     }>,
 *     access_control?: list<array{ // Default: []
 *         request_matcher?: scalar|Param|null, // Default: null
 *         requires_channel?: scalar|Param|null, // Default: null
 *         path?: scalar|Param|null, // Use the urldecoded format. // Default: null
 *         host?: scalar|Param|null, // Default: null
 *         port?: int|Param, // Default: null
 *         ips?: string|list<scalar|Param|null>,
 *         attributes?: array<string, scalar|Param|null>,
 *         route?: scalar|Param|null, // Default: null
 *         methods?: string|list<scalar|Param|null>,
 *         allow_if?: scalar|Param|null, // Default: null
 *         roles?: string|list<scalar|Param|null>,
 *     }>,
 *     role_hierarchy?: array<string, string|list<scalar|Param|null>>,
 * }
 * @psalm-type TwigConfig = array{
 *     form_themes?: list<scalar|Param|null>,
 *     globals?: array<string, array{ // Default: []
 *         id?: scalar|Param|null,
 *         type?: scalar|Param|null,
 *         value?: mixed,
 *     }>,
 *     autoescape_service?: scalar|Param|null, // Default: null
 *     autoescape_service_method?: scalar|Param|null, // Default: null
 *     base_template_class?: scalar|Param|null, // Deprecated: The child node "base_template_class" at path "twig.base_template_class" is deprecated.
 *     cache?: scalar|Param|null, // Default: true
 *     charset?: scalar|Param|null, // Default: "%kernel.charset%"
 *     debug?: bool|Param, // Default: "%kernel.debug%"
 *     strict_variables?: bool|Param, // Default: "%kernel.debug%"
 *     auto_reload?: scalar|Param|null,
 *     optimizations?: int|Param,
 *     default_path?: scalar|Param|null, // The default path used to load templates. // Default: "%kernel.project_dir%/templates"
 *     file_name_pattern?: string|list<scalar|Param|null>,
 *     paths?: array<string, mixed>,
 *     date?: array{ // The default format options used by the date filter.
 *         format?: scalar|Param|null, // Default: "F j, Y H:i"
 *         interval_format?: scalar|Param|null, // Default: "%d days"
 *         timezone?: scalar|Param|null, // The timezone used when formatting dates, when set to null, the timezone returned by date_default_timezone_get() is used. // Default: null
 *     },
 *     number_format?: array{ // The default format options for the number_format filter.
 *         decimals?: int|Param, // Default: 0
 *         decimal_point?: scalar|Param|null, // Default: "."
 *         thousands_separator?: scalar|Param|null, // Default: ","
 *     },
 *     mailer?: array{
 *         html_to_text_converter?: scalar|Param|null, // A service implementing the "Symfony\Component\Mime\HtmlToTextConverter\HtmlToTextConverterInterface". // Default: null
 *     },
 * }
 * @psalm-type MonologConfig = array{
 *     use_microseconds?: scalar|Param|null, // Default: true
 *     channels?: list<scalar|Param|null>,
 *     handlers?: array<string, array{ // Default: []
 *         type?: scalar|Param|null,
 *         id?: scalar|Param|null,
 *         enabled?: bool|Param, // Default: true
 *         priority?: scalar|Param|null, // Default: 0
 *         level?: scalar|Param|null, // Default: "DEBUG"
 *         bubble?: bool|Param, // Default: true
 *         interactive_only?: bool|Param, // Default: false
 *         app_name?: scalar|Param|null, // Default: null
 *         fill_extra_context?: bool|Param, // Default: false
 *         include_stacktraces?: bool|Param, // Default: false
 *         process_psr_3_messages?: array{
 *             enabled?: bool|Param|null, // Default: null
 *             date_format?: scalar|Param|null,
 *             remove_used_context_fields?: bool|Param,
 *         },
 *         path?: scalar|Param|null, // Default: "%kernel.logs_dir%/%kernel.environment%.log"
 *         file_permission?: scalar|Param|null, // Default: null
 *         use_locking?: bool|Param, // Default: false
 *         filename_format?: scalar|Param|null, // Default: "{filename}-{date}"
 *         date_format?: scalar|Param|null, // Default: "Y-m-d"
 *         ident?: scalar|Param|null, // Default: false
 *         logopts?: scalar|Param|null, // Default: 1
 *         facility?: scalar|Param|null, // Default: "user"
 *         max_files?: scalar|Param|null, // Default: 0
 *         action_level?: scalar|Param|null, // Default: "WARNING"
 *         activation_strategy?: scalar|Param|null, // Default: null
 *         stop_buffering?: bool|Param, // Default: true
 *         passthru_level?: scalar|Param|null, // Default: null
 *         excluded_404s?: list<scalar|Param|null>,
 *         excluded_http_codes?: list<array{ // Default: []
 *             code?: scalar|Param|null,
 *             urls?: list<scalar|Param|null>,
 *         }>,
 *         accepted_levels?: list<scalar|Param|null>,
 *         min_level?: scalar|Param|null, // Default: "DEBUG"
 *         max_level?: scalar|Param|null, // Default: "EMERGENCY"
 *         buffer_size?: scalar|Param|null, // Default: 0
 *         flush_on_overflow?: bool|Param, // Default: false
 *         handler?: scalar|Param|null,
 *         url?: scalar|Param|null,
 *         exchange?: scalar|Param|null,
 *         exchange_name?: scalar|Param|null, // Default: "log"
 *         room?: scalar|Param|null,
 *         message_format?: scalar|Param|null, // Default: "text"
 *         api_version?: scalar|Param|null, // Default: null
 *         channel?: scalar|Param|null, // Default: null
 *         bot_name?: scalar|Param|null, // Default: "Monolog"
 *         use_attachment?: scalar|Param|null, // Default: true
 *         use_short_attachment?: scalar|Param|null, // Default: false
 *         include_extra?: scalar|Param|null, // Default: false
 *         icon_emoji?: scalar|Param|null, // Default: null
 *         webhook_url?: scalar|Param|null,
 *         exclude_fields?: list<scalar|Param|null>,
 *         team?: scalar|Param|null,
 *         notify?: scalar|Param|null, // Default: false
 *         nickname?: scalar|Param|null, // Default: "Monolog"
 *         token?: scalar|Param|null,
 *         region?: scalar|Param|null,
 *         source?: scalar|Param|null,
 *         use_ssl?: bool|Param, // Default: true
 *         user?: mixed,
 *         title?: scalar|Param|null, // Default: null
 *         host?: scalar|Param|null, // Default: null
 *         port?: scalar|Param|null, // Default: 514
 *         config?: list<scalar|Param|null>,
 *         members?: list<scalar|Param|null>,
 *         connection_string?: scalar|Param|null,
 *         timeout?: scalar|Param|null,
 *         time?: scalar|Param|null, // Default: 60
 *         deduplication_level?: scalar|Param|null, // Default: 400
 *         store?: scalar|Param|null, // Default: null
 *         connection_timeout?: scalar|Param|null,
 *         persistent?: bool|Param,
 *         dsn?: scalar|Param|null,
 *         hub_id?: scalar|Param|null, // Default: null
 *         client_id?: scalar|Param|null, // Default: null
 *         auto_log_stacks?: scalar|Param|null, // Default: false
 *         release?: scalar|Param|null, // Default: null
 *         environment?: scalar|Param|null, // Default: null
 *         message_type?: scalar|Param|null, // Default: 0
 *         parse_mode?: scalar|Param|null, // Default: null
 *         disable_webpage_preview?: bool|Param|null, // Default: null
 *         disable_notification?: bool|Param|null, // Default: null
 *         split_long_messages?: bool|Param, // Default: false
 *         delay_between_messages?: bool|Param, // Default: false
 *         topic?: int|Param, // Default: null
 *         factor?: int|Param, // Default: 1
 *         tags?: string|list<scalar|Param|null>,
 *         console_formater_options?: mixed, // Deprecated: "monolog.handlers..console_formater_options.console_formater_options" is deprecated, use "monolog.handlers..console_formater_options.console_formatter_options" instead.
 *         console_formatter_options?: mixed, // Default: []
 *         formatter?: scalar|Param|null,
 *         nested?: bool|Param, // Default: false
 *         publisher?: string|array{
 *             id?: scalar|Param|null,
 *             hostname?: scalar|Param|null,
 *             port?: scalar|Param|null, // Default: 12201
 *             chunk_size?: scalar|Param|null, // Default: 1420
 *             encoder?: "json"|"compressed_json"|Param,
 *         },
 *         mongo?: string|array{
 *             id?: scalar|Param|null,
 *             host?: scalar|Param|null,
 *             port?: scalar|Param|null, // Default: 27017
 *             user?: scalar|Param|null,
 *             pass?: scalar|Param|null,
 *             database?: scalar|Param|null, // Default: "monolog"
 *             collection?: scalar|Param|null, // Default: "logs"
 *         },
 *         mongodb?: string|array{
 *             id?: scalar|Param|null, // ID of a MongoDB\Client service
 *             uri?: scalar|Param|null,
 *             username?: scalar|Param|null,
 *             password?: scalar|Param|null,
 *             database?: scalar|Param|null, // Default: "monolog"
 *             collection?: scalar|Param|null, // Default: "logs"
 *         },
 *         elasticsearch?: string|array{
 *             id?: scalar|Param|null,
 *             hosts?: list<scalar|Param|null>,
 *             host?: scalar|Param|null,
 *             port?: scalar|Param|null, // Default: 9200
 *             transport?: scalar|Param|null, // Default: "Http"
 *             user?: scalar|Param|null, // Default: null
 *             password?: scalar|Param|null, // Default: null
 *         },
 *         index?: scalar|Param|null, // Default: "monolog"
 *         document_type?: scalar|Param|null, // Default: "logs"
 *         ignore_error?: scalar|Param|null, // Default: false
 *         redis?: string|array{
 *             id?: scalar|Param|null,
 *             host?: scalar|Param|null,
 *             password?: scalar|Param|null, // Default: null
 *             port?: scalar|Param|null, // Default: 6379
 *             database?: scalar|Param|null, // Default: 0
 *             key_name?: scalar|Param|null, // Default: "monolog_redis"
 *         },
 *         predis?: string|array{
 *             id?: scalar|Param|null,
 *             host?: scalar|Param|null,
 *         },
 *         from_email?: scalar|Param|null,
 *         to_email?: string|list<scalar|Param|null>,
 *         subject?: scalar|Param|null,
 *         content_type?: scalar|Param|null, // Default: null
 *         headers?: list<scalar|Param|null>,
 *         mailer?: scalar|Param|null, // Default: null
 *         email_prototype?: string|array{
 *             id?: scalar|Param|null,
 *             method?: scalar|Param|null, // Default: null
 *         },
 *         lazy?: bool|Param, // Default: true
 *         verbosity_levels?: array{
 *             VERBOSITY_QUIET?: scalar|Param|null, // Default: "ERROR"
 *             VERBOSITY_NORMAL?: scalar|Param|null, // Default: "WARNING"
 *             VERBOSITY_VERBOSE?: scalar|Param|null, // Default: "NOTICE"
 *             VERBOSITY_VERY_VERBOSE?: scalar|Param|null, // Default: "INFO"
 *             VERBOSITY_DEBUG?: scalar|Param|null, // Default: "DEBUG"
 *         },
 *         channels?: string|array{
 *             type?: scalar|Param|null,
 *             elements?: list<scalar|Param|null>,
 *         },
 *     }>,
 * }
 * @psalm-type DoctrineConfig = array{
 *     dbal?: array{
 *         default_connection?: scalar|Param|null,
 *         types?: array<string, string|array{ // Default: []
 *             class?: scalar|Param|null,
 *             commented?: bool|Param, // Deprecated: The doctrine-bundle type commenting features were removed; the corresponding config parameter was deprecated in 2.0 and will be dropped in 3.0.
 *         }>,
 *         driver_schemes?: array<string, scalar|Param|null>,
 *         connections?: array<string, array{ // Default: []
 *             url?: scalar|Param|null, // A URL with connection information; any parameter value parsed from this string will override explicitly set parameters
 *             dbname?: scalar|Param|null,
 *             host?: scalar|Param|null, // Defaults to "localhost" at runtime.
 *             port?: scalar|Param|null, // Defaults to null at runtime.
 *             user?: scalar|Param|null, // Defaults to "root" at runtime.
 *             password?: scalar|Param|null, // Defaults to null at runtime.
 *             override_url?: bool|Param, // Deprecated: The "doctrine.dbal.override_url" configuration key is deprecated.
 *             dbname_suffix?: scalar|Param|null, // Adds the given suffix to the configured database name, this option has no effects for the SQLite platform
 *             application_name?: scalar|Param|null,
 *             charset?: scalar|Param|null,
 *             path?: scalar|Param|null,
 *             memory?: bool|Param,
 *             unix_socket?: scalar|Param|null, // The unix socket to use for MySQL
 *             persistent?: bool|Param, // True to use as persistent connection for the ibm_db2 driver
 *             protocol?: scalar|Param|null, // The protocol to use for the ibm_db2 driver (default to TCPIP if omitted)
 *             service?: bool|Param, // True to use SERVICE_NAME as connection parameter instead of SID for Oracle
 *             servicename?: scalar|Param|null, // Overrules dbname parameter if given and used as SERVICE_NAME or SID connection parameter for Oracle depending on the service parameter.
 *             sessionMode?: scalar|Param|null, // The session mode to use for the oci8 driver
 *             server?: scalar|Param|null, // The name of a running database server to connect to for SQL Anywhere.
 *             default_dbname?: scalar|Param|null, // Override the default database (postgres) to connect to for PostgreSQL connexion.
 *             sslmode?: scalar|Param|null, // Determines whether or with what priority a SSL TCP/IP connection will be negotiated with the server for PostgreSQL.
 *             sslrootcert?: scalar|Param|null, // The name of a file containing SSL certificate authority (CA) certificate(s). If the file exists, the server's certificate will be verified to be signed by one of these authorities.
 *             sslcert?: scalar|Param|null, // The path to the SSL client certificate file for PostgreSQL.
 *             sslkey?: scalar|Param|null, // The path to the SSL client key file for PostgreSQL.
 *             sslcrl?: scalar|Param|null, // The file name of the SSL certificate revocation list for PostgreSQL.
 *             pooled?: bool|Param, // True to use a pooled server with the oci8/pdo_oracle driver
 *             MultipleActiveResultSets?: bool|Param, // Configuring MultipleActiveResultSets for the pdo_sqlsrv driver
 *             use_savepoints?: bool|Param, // Use savepoints for nested transactions
 *             instancename?: scalar|Param|null, // Optional parameter, complete whether to add the INSTANCE_NAME parameter in the connection. It is generally used to connect to an Oracle RAC server to select the name of a particular instance.
 *             connectstring?: scalar|Param|null, // Complete Easy Connect connection descriptor, see https://docs.oracle.com/database/121/NETAG/naming.htm.When using this option, you will still need to provide the user and password parameters, but the other parameters will no longer be used. Note that when using this parameter, the getHost and getPort methods from Doctrine\DBAL\Connection will no longer function as expected.
 *             driver?: scalar|Param|null, // Default: "pdo_mysql"
 *             platform_service?: scalar|Param|null, // Deprecated: The "platform_service" configuration key is deprecated since doctrine-bundle 2.9. DBAL 4 will not support setting a custom platform via connection params anymore.
 *             auto_commit?: bool|Param,
 *             schema_filter?: scalar|Param|null,
 *             logging?: bool|Param, // Default: true
 *             profiling?: bool|Param, // Default: true
 *             profiling_collect_backtrace?: bool|Param, // Enables collecting backtraces when profiling is enabled // Default: false
 *             profiling_collect_schema_errors?: bool|Param, // Enables collecting schema errors when profiling is enabled // Default: true
 *             disable_type_comments?: bool|Param,
 *             server_version?: scalar|Param|null,
 *             idle_connection_ttl?: int|Param, // Default: 600
 *             driver_class?: scalar|Param|null,
 *             wrapper_class?: scalar|Param|null,
 *             keep_slave?: bool|Param, // Deprecated: The "keep_slave" configuration key is deprecated since doctrine-bundle 2.2. Use the "keep_replica" configuration key instead.
 *             keep_replica?: bool|Param,
 *             options?: array<string, mixed>,
 *             mapping_types?: array<string, scalar|Param|null>,
 *             default_table_options?: array<string, scalar|Param|null>,
 *             schema_manager_factory?: scalar|Param|null, // Default: "doctrine.dbal.legacy_schema_manager_factory"
 *             result_cache?: scalar|Param|null,
 *             slaves?: array<string, array{ // Default: []
 *                 url?: scalar|Param|null, // A URL with connection information; any parameter value parsed from this string will override explicitly set parameters
 *                 dbname?: scalar|Param|null,
 *                 host?: scalar|Param|null, // Defaults to "localhost" at runtime.
 *                 port?: scalar|Param|null, // Defaults to null at runtime.
 *                 user?: scalar|Param|null, // Defaults to "root" at runtime.
 *                 password?: scalar|Param|null, // Defaults to null at runtime.
 *                 override_url?: bool|Param, // Deprecated: The "doctrine.dbal.override_url" configuration key is deprecated.
 *                 dbname_suffix?: scalar|Param|null, // Adds the given suffix to the configured database name, this option has no effects for the SQLite platform
 *                 application_name?: scalar|Param|null,
 *                 charset?: scalar|Param|null,
 *                 path?: scalar|Param|null,
 *                 memory?: bool|Param,
 *                 unix_socket?: scalar|Param|null, // The unix socket to use for MySQL
 *                 persistent?: bool|Param, // True to use as persistent connection for the ibm_db2 driver
 *                 protocol?: scalar|Param|null, // The protocol to use for the ibm_db2 driver (default to TCPIP if omitted)
 *                 service?: bool|Param, // True to use SERVICE_NAME as connection parameter instead of SID for Oracle
 *                 servicename?: scalar|Param|null, // Overrules dbname parameter if given and used as SERVICE_NAME or SID connection parameter for Oracle depending on the service parameter.
 *                 sessionMode?: scalar|Param|null, // The session mode to use for the oci8 driver
 *                 server?: scalar|Param|null, // The name of a running database server to connect to for SQL Anywhere.
 *                 default_dbname?: scalar|Param|null, // Override the default database (postgres) to connect to for PostgreSQL connexion.
 *                 sslmode?: scalar|Param|null, // Determines whether or with what priority a SSL TCP/IP connection will be negotiated with the server for PostgreSQL.
 *                 sslrootcert?: scalar|Param|null, // The name of a file containing SSL certificate authority (CA) certificate(s). If the file exists, the server's certificate will be verified to be signed by one of these authorities.
 *                 sslcert?: scalar|Param|null, // The path to the SSL client certificate file for PostgreSQL.
 *                 sslkey?: scalar|Param|null, // The path to the SSL client key file for PostgreSQL.
 *                 sslcrl?: scalar|Param|null, // The file name of the SSL certificate revocation list for PostgreSQL.
 *                 pooled?: bool|Param, // True to use a pooled server with the oci8/pdo_oracle driver
 *                 MultipleActiveResultSets?: bool|Param, // Configuring MultipleActiveResultSets for the pdo_sqlsrv driver
 *                 use_savepoints?: bool|Param, // Use savepoints for nested transactions
 *                 instancename?: scalar|Param|null, // Optional parameter, complete whether to add the INSTANCE_NAME parameter in the connection. It is generally used to connect to an Oracle RAC server to select the name of a particular instance.
 *                 connectstring?: scalar|Param|null, // Complete Easy Connect connection descriptor, see https://docs.oracle.com/database/121/NETAG/naming.htm.When using this option, you will still need to provide the user and password parameters, but the other parameters will no longer be used. Note that when using this parameter, the getHost and getPort methods from Doctrine\DBAL\Connection will no longer function as expected.
 *             }>,
 *             replicas?: array<string, array{ // Default: []
 *                 url?: scalar|Param|null, // A URL with connection information; any parameter value parsed from this string will override explicitly set parameters
 *                 dbname?: scalar|Param|null,
 *                 host?: scalar|Param|null, // Defaults to "localhost" at runtime.
 *                 port?: scalar|Param|null, // Defaults to null at runtime.
 *                 user?: scalar|Param|null, // Defaults to "root" at runtime.
 *                 password?: scalar|Param|null, // Defaults to null at runtime.
 *                 override_url?: bool|Param, // Deprecated: The "doctrine.dbal.override_url" configuration key is deprecated.
 *                 dbname_suffix?: scalar|Param|null, // Adds the given suffix to the configured database name, this option has no effects for the SQLite platform
 *                 application_name?: scalar|Param|null,
 *                 charset?: scalar|Param|null,
 *                 path?: scalar|Param|null,
 *                 memory?: bool|Param,
 *                 unix_socket?: scalar|Param|null, // The unix socket to use for MySQL
 *                 persistent?: bool|Param, // True to use as persistent connection for the ibm_db2 driver
 *                 protocol?: scalar|Param|null, // The protocol to use for the ibm_db2 driver (default to TCPIP if omitted)
 *                 service?: bool|Param, // True to use SERVICE_NAME as connection parameter instead of SID for Oracle
 *                 servicename?: scalar|Param|null, // Overrules dbname parameter if given and used as SERVICE_NAME or SID connection parameter for Oracle depending on the service parameter.
 *                 sessionMode?: scalar|Param|null, // The session mode to use for the oci8 driver
 *                 server?: scalar|Param|null, // The name of a running database server to connect to for SQL Anywhere.
 *                 default_dbname?: scalar|Param|null, // Override the default database (postgres) to connect to for PostgreSQL connexion.
 *                 sslmode?: scalar|Param|null, // Determines whether or with what priority a SSL TCP/IP connection will be negotiated with the server for PostgreSQL.
 *                 sslrootcert?: scalar|Param|null, // The name of a file containing SSL certificate authority (CA) certificate(s). If the file exists, the server's certificate will be verified to be signed by one of these authorities.
 *                 sslcert?: scalar|Param|null, // The path to the SSL client certificate file for PostgreSQL.
 *                 sslkey?: scalar|Param|null, // The path to the SSL client key file for PostgreSQL.
 *                 sslcrl?: scalar|Param|null, // The file name of the SSL certificate revocation list for PostgreSQL.
 *                 pooled?: bool|Param, // True to use a pooled server with the oci8/pdo_oracle driver
 *                 MultipleActiveResultSets?: bool|Param, // Configuring MultipleActiveResultSets for the pdo_sqlsrv driver
 *                 use_savepoints?: bool|Param, // Use savepoints for nested transactions
 *                 instancename?: scalar|Param|null, // Optional parameter, complete whether to add the INSTANCE_NAME parameter in the connection. It is generally used to connect to an Oracle RAC server to select the name of a particular instance.
 *                 connectstring?: scalar|Param|null, // Complete Easy Connect connection descriptor, see https://docs.oracle.com/database/121/NETAG/naming.htm.When using this option, you will still need to provide the user and password parameters, but the other parameters will no longer be used. Note that when using this parameter, the getHost and getPort methods from Doctrine\DBAL\Connection will no longer function as expected.
 *             }>,
 *         }>,
 *     },
 *     orm?: array{
 *         default_entity_manager?: scalar|Param|null,
 *         auto_generate_proxy_classes?: scalar|Param|null, // Auto generate mode possible values are: "NEVER", "ALWAYS", "FILE_NOT_EXISTS", "EVAL", "FILE_NOT_EXISTS_OR_CHANGED", this option is ignored when the "enable_native_lazy_objects" option is true // Default: false
 *         enable_lazy_ghost_objects?: bool|Param, // Enables the new implementation of proxies based on lazy ghosts instead of using the legacy implementation // Default: false
 *         enable_native_lazy_objects?: bool|Param, // Enables the new native implementation of PHP lazy objects instead of generated proxies // Default: false
 *         proxy_dir?: scalar|Param|null, // Configures the path where generated proxy classes are saved when using non-native lazy objects, this option is ignored when the "enable_native_lazy_objects" option is true // Default: "%kernel.build_dir%/doctrine/orm/Proxies"
 *         proxy_namespace?: scalar|Param|null, // Defines the root namespace for generated proxy classes when using non-native lazy objects, this option is ignored when the "enable_native_lazy_objects" option is true // Default: "Proxies"
 *         controller_resolver?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *             auto_mapping?: bool|Param|null, // Set to false to disable using route placeholders as lookup criteria when the primary key doesn't match the argument name // Default: null
 *             evict_cache?: bool|Param, // Set to true to fetch the entity from the database instead of using the cache, if any // Default: false
 *         },
 *         entity_managers?: array<string, array{ // Default: []
 *             query_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             metadata_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             result_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             entity_listeners?: array{
 *                 entities?: array<string, array{ // Default: []
 *                     listeners?: array<string, array{ // Default: []
 *                         events?: list<array{ // Default: []
 *                             type?: scalar|Param|null,
 *                             method?: scalar|Param|null, // Default: null
 *                         }>,
 *                     }>,
 *                 }>,
 *             },
 *             connection?: scalar|Param|null,
 *             class_metadata_factory_name?: scalar|Param|null, // Default: "Doctrine\\ORM\\Mapping\\ClassMetadataFactory"
 *             default_repository_class?: scalar|Param|null, // Default: "Doctrine\\ORM\\EntityRepository"
 *             auto_mapping?: scalar|Param|null, // Default: false
 *             naming_strategy?: scalar|Param|null, // Default: "doctrine.orm.naming_strategy.default"
 *             quote_strategy?: scalar|Param|null, // Default: "doctrine.orm.quote_strategy.default"
 *             typed_field_mapper?: scalar|Param|null, // Default: "doctrine.orm.typed_field_mapper.default"
 *             entity_listener_resolver?: scalar|Param|null, // Default: null
 *             fetch_mode_subselect_batch_size?: scalar|Param|null,
 *             repository_factory?: scalar|Param|null, // Default: "doctrine.orm.container_repository_factory"
 *             schema_ignore_classes?: list<scalar|Param|null>,
 *             report_fields_where_declared?: bool|Param, // Set to "true" to opt-in to the new mapping driver mode that was added in Doctrine ORM 2.16 and will be mandatory in ORM 3.0. See https://github.com/doctrine/orm/pull/10455. // Default: false
 *             validate_xml_mapping?: bool|Param, // Set to "true" to opt-in to the new mapping driver mode that was added in Doctrine ORM 2.14. See https://github.com/doctrine/orm/pull/6728. // Default: false
 *             second_level_cache?: array{
 *                 region_cache_driver?: string|array{
 *                     type?: scalar|Param|null, // Default: null
 *                     id?: scalar|Param|null,
 *                     pool?: scalar|Param|null,
 *                 },
 *                 region_lock_lifetime?: scalar|Param|null, // Default: 60
 *                 log_enabled?: bool|Param, // Default: true
 *                 region_lifetime?: scalar|Param|null, // Default: 3600
 *                 enabled?: bool|Param, // Default: true
 *                 factory?: scalar|Param|null,
 *                 regions?: array<string, array{ // Default: []
 *                     cache_driver?: string|array{
 *                         type?: scalar|Param|null, // Default: null
 *                         id?: scalar|Param|null,
 *                         pool?: scalar|Param|null,
 *                     },
 *                     lock_path?: scalar|Param|null, // Default: "%kernel.cache_dir%/doctrine/orm/slc/filelock"
 *                     lock_lifetime?: scalar|Param|null, // Default: 60
 *                     type?: scalar|Param|null, // Default: "default"
 *                     lifetime?: scalar|Param|null, // Default: 0
 *                     service?: scalar|Param|null,
 *                     name?: scalar|Param|null,
 *                 }>,
 *                 loggers?: array<string, array{ // Default: []
 *                     name?: scalar|Param|null,
 *                     service?: scalar|Param|null,
 *                 }>,
 *             },
 *             hydrators?: array<string, scalar|Param|null>,
 *             mappings?: array<string, bool|string|array{ // Default: []
 *                 mapping?: scalar|Param|null, // Default: true
 *                 type?: scalar|Param|null,
 *                 dir?: scalar|Param|null,
 *                 alias?: scalar|Param|null,
 *                 prefix?: scalar|Param|null,
 *                 is_bundle?: bool|Param,
 *             }>,
 *             dql?: array{
 *                 string_functions?: array<string, scalar|Param|null>,
 *                 numeric_functions?: array<string, scalar|Param|null>,
 *                 datetime_functions?: array<string, scalar|Param|null>,
 *             },
 *             filters?: array<string, string|array{ // Default: []
 *                 class?: scalar|Param|null,
 *                 enabled?: bool|Param, // Default: false
 *                 parameters?: array<string, mixed>,
 *             }>,
 *             identity_generation_preferences?: array<string, scalar|Param|null>,
 *         }>,
 *         resolve_target_entities?: array<string, scalar|Param|null>,
 *     },
 * }
 * @psalm-type WebpackEncoreConfig = array{
 *     output_path?: scalar|Param|null, // The path where Encore is building the assets - i.e. Encore.setOutputPath()
 *     crossorigin?: false|"anonymous"|"use-credentials"|Param, // crossorigin value when Encore.enableIntegrityHashes() is used, can be false (default), anonymous or use-credentials // Default: false
 *     preload?: bool|Param, // preload all rendered script and link tags automatically via the http2 Link header. // Default: false
 *     cache?: bool|Param, // Enable caching of the entry point file(s) // Default: false
 *     strict_mode?: bool|Param, // Throw an exception if the entrypoints.json file is missing or an entry is missing from the data // Default: true
 *     builds?: array<string, scalar|Param|null>,
 *     script_attributes?: array<string, scalar|Param|null>,
 *     link_attributes?: array<string, scalar|Param|null>,
 * }
 * @psalm-type DoctrineMigrationsConfig = array{
 *     enable_service_migrations?: bool|Param, // Whether to enable fetching migrations from the service container. // Default: false
 *     migrations_paths?: array<string, scalar|Param|null>,
 *     services?: array<string, scalar|Param|null>,
 *     factories?: array<string, scalar|Param|null>,
 *     storage?: array{ // Storage to use for migration status metadata.
 *         table_storage?: array{ // The default metadata storage, implemented as a table in the database.
 *             table_name?: scalar|Param|null, // Default: null
 *             version_column_name?: scalar|Param|null, // Default: null
 *             version_column_length?: scalar|Param|null, // Default: null
 *             executed_at_column_name?: scalar|Param|null, // Default: null
 *             execution_time_column_name?: scalar|Param|null, // Default: null
 *         },
 *     },
 *     migrations?: list<scalar|Param|null>,
 *     connection?: scalar|Param|null, // Connection name to use for the migrations database. // Default: null
 *     em?: scalar|Param|null, // Entity manager name to use for the migrations database (available when doctrine/orm is installed). // Default: null
 *     all_or_nothing?: scalar|Param|null, // Run all migrations in a transaction. // Default: false
 *     check_database_platform?: scalar|Param|null, // Adds an extra check in the generated migrations to allow execution only on the same platform as they were initially generated on. // Default: true
 *     custom_template?: scalar|Param|null, // Custom template path for generated migration classes. // Default: null
 *     organize_migrations?: scalar|Param|null, // Organize migrations mode. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false // Default: false
 *     enable_profiler?: bool|Param, // Whether or not to enable the profiler collector to calculate and visualize migration status. This adds some queries overhead. // Default: false
 *     transactional?: bool|Param, // Whether or not to wrap migrations in a single transaction. // Default: true
 * }
 * @psalm-type PayumConfig = array{
 *     security?: array{
 *         token_storage?: array<string, array{ // Default: []
 *             filesystem?: array{
 *                 storage_dir?: scalar|Param|null,
 *                 id_property?: scalar|Param|null, // Default: null
 *             },
 *             doctrine?: string|array{
 *                 driver?: scalar|Param|null,
 *             },
 *             custom?: string|array{
 *                 service?: scalar|Param|null,
 *             },
 *             propel1?: array<mixed>,
 *             propel2?: array<mixed>,
 *         }>,
 *     },
 *     dynamic_gateways?: array{
 *         sonata_admin?: bool|Param, // Default: false
 *         config_storage?: array<string, array{ // Default: []
 *             filesystem?: array{
 *                 storage_dir?: scalar|Param|null,
 *                 id_property?: scalar|Param|null, // Default: null
 *             },
 *             doctrine?: string|array{
 *                 driver?: scalar|Param|null,
 *             },
 *             custom?: string|array{
 *                 service?: scalar|Param|null,
 *             },
 *             propel1?: array<mixed>,
 *             propel2?: array<mixed>,
 *         }>,
 *         encryption?: array{
 *             defuse_secret_key?: scalar|Param|null,
 *         },
 *     },
 *     gateways?: array<string, mixed>,
 *     storages?: array<string, array{ // Default: []
 *         extension?: array{
 *             all?: bool|Param, // Default: true
 *             gateways?: array<string, scalar|Param|null>,
 *             factories?: array<string, scalar|Param|null>,
 *         },
 *         filesystem?: array{
 *             storage_dir?: scalar|Param|null,
 *             id_property?: scalar|Param|null, // Default: null
 *         },
 *         doctrine?: string|array{
 *             driver?: scalar|Param|null,
 *         },
 *         custom?: string|array{
 *             service?: scalar|Param|null,
 *         },
 *         propel1?: array<mixed>,
 *         propel2?: array<mixed>,
 *     }>,
 * }
 * @psalm-type StofDoctrineExtensionsConfig = array{
 *     orm?: array<string, array{ // Default: []
 *         translatable?: scalar|Param|null, // Default: false
 *         timestampable?: scalar|Param|null, // Default: false
 *         blameable?: scalar|Param|null, // Default: false
 *         sluggable?: scalar|Param|null, // Default: false
 *         tree?: scalar|Param|null, // Default: false
 *         loggable?: scalar|Param|null, // Default: false
 *         ip_traceable?: scalar|Param|null, // Default: false
 *         sortable?: scalar|Param|null, // Default: false
 *         softdeleteable?: scalar|Param|null, // Default: false
 *         uploadable?: scalar|Param|null, // Default: false
 *         reference_integrity?: scalar|Param|null, // Default: false
 *     }>,
 *     mongodb?: array<string, array{ // Default: []
 *         translatable?: scalar|Param|null, // Default: false
 *         timestampable?: scalar|Param|null, // Default: false
 *         blameable?: scalar|Param|null, // Default: false
 *         sluggable?: scalar|Param|null, // Default: false
 *         tree?: scalar|Param|null, // Default: false
 *         loggable?: scalar|Param|null, // Default: false
 *         ip_traceable?: scalar|Param|null, // Default: false
 *         sortable?: scalar|Param|null, // Default: false
 *         softdeleteable?: scalar|Param|null, // Default: false
 *         uploadable?: scalar|Param|null, // Default: false
 *         reference_integrity?: scalar|Param|null, // Default: false
 *     }>,
 *     class?: array{
 *         translatable?: scalar|Param|null, // Default: "Gedmo\\Translatable\\TranslatableListener"
 *         timestampable?: scalar|Param|null, // Default: "Gedmo\\Timestampable\\TimestampableListener"
 *         blameable?: scalar|Param|null, // Default: "Gedmo\\Blameable\\BlameableListener"
 *         sluggable?: scalar|Param|null, // Default: "Gedmo\\Sluggable\\SluggableListener"
 *         tree?: scalar|Param|null, // Default: "Gedmo\\Tree\\TreeListener"
 *         loggable?: scalar|Param|null, // Default: "Gedmo\\Loggable\\LoggableListener"
 *         sortable?: scalar|Param|null, // Default: "Gedmo\\Sortable\\SortableListener"
 *         softdeleteable?: scalar|Param|null, // Default: "Gedmo\\SoftDeleteable\\SoftDeleteableListener"
 *         uploadable?: scalar|Param|null, // Default: "Gedmo\\Uploadable\\UploadableListener"
 *         reference_integrity?: scalar|Param|null, // Default: "Gedmo\\ReferenceIntegrity\\ReferenceIntegrityListener"
 *     },
 *     softdeleteable?: array{
 *         handle_post_flush_event?: bool|Param, // Default: false
 *     },
 *     uploadable?: array{
 *         default_file_path?: scalar|Param|null, // Default: null
 *         mime_type_guesser_class?: scalar|Param|null, // Default: "Stof\\DoctrineExtensionsBundle\\Uploadable\\MimeTypeGuesserAdapter"
 *         default_file_info_class?: scalar|Param|null, // Default: "Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadedFileInfo"
 *         validate_writable_directory?: bool|Param, // Default: true
 *     },
 *     default_locale?: scalar|Param|null, // Default: "en"
 *     translation_fallback?: bool|Param, // Default: false
 *     persist_default_translation?: bool|Param, // Default: false
 *     skip_translation_on_load?: bool|Param, // Default: false
 *     metadata_cache_pool?: scalar|Param|null, // Default: null
 * }
 * @psalm-type ApiPlatformConfig = array{
 *     title?: scalar|Param|null, // The title of the API. // Default: ""
 *     description?: scalar|Param|null, // The description of the API. // Default: ""
 *     version?: scalar|Param|null, // The version of the API. // Default: "0.0.0"
 *     show_webby?: bool|Param, // If true, show Webby on the documentation page // Default: true
 *     use_symfony_listeners?: bool|Param, // Uses Symfony event listeners instead of the ApiPlatform\Symfony\Controller\MainController. // Default: false
 *     name_converter?: scalar|Param|null, // Specify a name converter to use. // Default: null
 *     asset_package?: scalar|Param|null, // Specify an asset package name to use. // Default: null
 *     path_segment_name_generator?: scalar|Param|null, // Specify a path name generator to use. // Default: "api_platform.metadata.path_segment_name_generator.underscore"
 *     inflector?: scalar|Param|null, // Specify an inflector to use. // Default: "api_platform.metadata.inflector"
 *     validator?: array{
 *         serialize_payload_fields?: mixed, // Set to null to serialize all payload fields when a validation error is thrown, or set the fields you want to include explicitly. // Default: []
 *         query_parameter_validation?: bool|Param, // Deprecated: Will be removed in API Platform 5.0. // Default: true
 *     },
 *     jsonapi?: array{
 *         use_iri_as_id?: bool|Param, // Set to false to use entity identifiers instead of IRIs as the "id" field in JSON:API responses. // Default: true
 *     },
 *     eager_loading?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         fetch_partial?: bool|Param, // Fetch only partial data according to serialization groups. If enabled, Doctrine ORM entities will not work as expected if any of the other fields are used. // Default: false
 *         max_joins?: int|Param, // Max number of joined relations before EagerLoading throws a RuntimeException // Default: 30
 *         force_eager?: bool|Param, // Force join on every relation. If disabled, it will only join relations having the EAGER fetch mode. // Default: true
 *     },
 *     handle_symfony_errors?: bool|Param, // Allows to handle symfony exceptions. // Default: false
 *     enable_swagger?: bool|Param, // Enable the Swagger documentation and export. // Default: true
 *     enable_json_streamer?: bool|Param, // Enable json streamer. // Default: false
 *     enable_swagger_ui?: bool|Param, // Enable Swagger UI // Default: true
 *     enable_re_doc?: bool|Param, // Enable ReDoc // Default: true
 *     enable_scalar?: bool|Param, // Enable Scalar API Reference // Default: true
 *     enable_entrypoint?: bool|Param, // Enable the entrypoint // Default: true
 *     enable_docs?: bool|Param, // Enable the docs // Default: true
 *     enable_profiler?: bool|Param, // Enable the data collector and the WebProfilerBundle integration. // Default: true
 *     enable_phpdoc_parser?: bool|Param, // Enable resource metadata collector using PHPStan PhpDocParser. // Default: true
 *     enable_link_security?: bool|Param, // Deprecated: This option is always enabled and will be removed in API Platform 5.0. // Enable security for Links (sub resources). // Default: true
 *     collection?: array{
 *         exists_parameter_name?: scalar|Param|null, // The name of the query parameter to filter on nullable field values. // Default: "exists"
 *         order?: scalar|Param|null, // The default order of results. // Default: "ASC"
 *         order_parameter_name?: scalar|Param|null, // The name of the query parameter to order results. // Default: "order"
 *         order_nulls_comparison?: "nulls_smallest"|"nulls_largest"|"nulls_always_first"|"nulls_always_last"|Param|null, // The nulls comparison strategy. // Default: null
 *         pagination?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *             page_parameter_name?: scalar|Param|null, // The default name of the parameter handling the page number. // Default: "page"
 *             enabled_parameter_name?: scalar|Param|null, // The name of the query parameter to enable or disable pagination. // Default: "pagination"
 *             items_per_page_parameter_name?: scalar|Param|null, // The name of the query parameter to set the number of items per page. // Default: "itemsPerPage"
 *             partial_parameter_name?: scalar|Param|null, // The name of the query parameter to enable or disable partial pagination. // Default: "partial"
 *         },
 *     },
 *     mapping?: array{
 *         imports?: list<scalar|Param|null>,
 *         paths?: list<scalar|Param|null>,
 *     },
 *     resource_class_directories?: list<scalar|Param|null>,
 *     serializer?: array{
 *         hydra_prefix?: bool|Param, // Use the "hydra:" prefix. // Default: false
 *     },
 *     doctrine?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     doctrine_mongodb_odm?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *     },
 *     oauth?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         clientId?: scalar|Param|null, // The oauth client id. // Default: ""
 *         clientSecret?: scalar|Param|null, // The OAuth client secret. Never use this parameter in your production environment. It exposes crucial security information. This feature is intended for dev/test environments only. Enable "oauth.pkce" instead // Default: ""
 *         pkce?: bool|Param, // Enable the oauth PKCE. // Default: false
 *         type?: scalar|Param|null, // The oauth type. // Default: "oauth2"
 *         flow?: scalar|Param|null, // The oauth flow grant type. // Default: "application"
 *         tokenUrl?: scalar|Param|null, // The oauth token url. // Default: ""
 *         authorizationUrl?: scalar|Param|null, // The oauth authentication url. // Default: ""
 *         refreshUrl?: scalar|Param|null, // The oauth refresh url. // Default: ""
 *         scopes?: list<scalar|Param|null>,
 *     },
 *     graphql?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         default_ide?: scalar|Param|null, // Default: "graphiql"
 *         graphiql?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *         },
 *         introspection?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *         },
 *         max_query_depth?: int|Param, // Default: 20
 *         graphql_playground?: bool|array{ // Deprecated: The "graphql_playground" configuration is deprecated and will be ignored.
 *             enabled?: bool|Param, // Default: false
 *         },
 *         max_query_complexity?: int|Param, // Default: 500
 *         nesting_separator?: scalar|Param|null, // The separator to use to filter nested fields. // Default: "_"
 *         collection?: array{
 *             pagination?: bool|array{
 *                 enabled?: bool|Param, // Default: true
 *             },
 *         },
 *     },
 *     swagger?: array{
 *         persist_authorization?: bool|Param, // Persist the SwaggerUI Authorization in the localStorage. // Default: false
 *         versions?: list<scalar|Param|null>,
 *         api_keys?: array<string, array{ // Default: []
 *             name?: scalar|Param|null, // The name of the header or query parameter containing the api key.
 *             type?: "query"|"header"|Param, // Whether the api key should be a query parameter or a header.
 *         }>,
 *         http_auth?: array<string, array{ // Default: []
 *             scheme?: scalar|Param|null, // The OpenAPI HTTP auth scheme, for example "bearer"
 *             bearerFormat?: scalar|Param|null, // The OpenAPI HTTP bearer format
 *         }>,
 *         swagger_ui_extra_configuration?: mixed, // To pass extra configuration to Swagger UI, like docExpansion or filter. // Default: []
 *     },
 *     http_cache?: array{
 *         public?: bool|Param|null, // To make all responses public by default. // Default: null
 *         invalidation?: bool|array{ // Enable the tags-based cache invalidation system.
 *             enabled?: bool|Param, // Default: false
 *             varnish_urls?: list<scalar|Param|null>,
 *             urls?: list<scalar|Param|null>,
 *             scoped_clients?: list<scalar|Param|null>,
 *             max_header_length?: int|Param, // Max header length supported by the cache server. // Default: 7500
 *             request_options?: mixed, // To pass options to the client charged with the request. // Default: []
 *             purger?: scalar|Param|null, // Specify a purger to use (available values: "api_platform.http_cache.purger.varnish.ban", "api_platform.http_cache.purger.varnish.xkey", "api_platform.http_cache.purger.souin"). // Default: "api_platform.http_cache.purger.varnish"
 *             xkey?: array{ // Deprecated: The "xkey" configuration is deprecated, use your own purger to customize surrogate keys or the appropriate parameters.
 *                 glue?: scalar|Param|null, // xkey glue between keys // Default: " "
 *             },
 *         },
 *     },
 *     mercure?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         hub_url?: scalar|Param|null, // The URL sent in the Link HTTP header. If not set, will default to the URL for MercureBundle's default hub. // Default: null
 *         include_type?: bool|Param, // Always include @type in updates (including delete ones). // Default: false
 *     },
 *     messenger?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     elasticsearch?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         hosts?: list<scalar|Param|null>,
 *         ssl_ca_bundle?: scalar|Param|null, // Path to the SSL CA bundle file for Elasticsearch SSL verification. // Default: null
 *         ssl_verification?: bool|Param, // Enable or disable SSL verification for Elasticsearch connections. // Default: true
 *         client?: "elasticsearch"|"opensearch"|Param, // The search engine client to use: "elasticsearch" or "opensearch". // Default: "elasticsearch"
 *     },
 *     openapi?: array{
 *         contact?: array{
 *             name?: scalar|Param|null, // The identifying name of the contact person/organization. // Default: null
 *             url?: scalar|Param|null, // The URL pointing to the contact information. MUST be in the format of a URL. // Default: null
 *             email?: scalar|Param|null, // The email address of the contact person/organization. MUST be in the format of an email address. // Default: null
 *         },
 *         termsOfService?: scalar|Param|null, // A URL to the Terms of Service for the API. MUST be in the format of a URL. // Default: null
 *         tags?: list<array{ // Default: []
 *             name?: scalar|Param|null,
 *             description?: scalar|Param|null, // Default: null
 *         }>,
 *         license?: array{
 *             name?: scalar|Param|null, // The license name used for the API. // Default: null
 *             url?: scalar|Param|null, // URL to the license used for the API. MUST be in the format of a URL. // Default: null
 *             identifier?: scalar|Param|null, // An SPDX license expression for the API. The identifier field is mutually exclusive of the url field. // Default: null
 *         },
 *         swagger_ui_extra_configuration?: mixed, // To pass extra configuration to Swagger UI, like docExpansion or filter. // Default: []
 *         scalar_extra_configuration?: mixed, // To pass extra configuration to Scalar API Reference, like theme or darkMode. // Default: []
 *         overrideResponses?: bool|Param, // Whether API Platform adds automatic responses to the OpenAPI documentation. // Default: true
 *         error_resource_class?: scalar|Param|null, // The class used to represent errors in the OpenAPI documentation. // Default: null
 *         validation_error_resource_class?: scalar|Param|null, // The class used to represent validation errors in the OpenAPI documentation. // Default: null
 *     },
 *     maker?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         namespace_prefix?: scalar|Param|null, // Add a prefix to all maker generated classes. e.g set it to "Api" to set the maker namespace to "App\Api\" (if the maker.root_namespace config is App). e.g. App\Api\State\MyStateProcessor // Default: ""
 *     },
 *     mcp?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         format?: scalar|Param|null, // The serialization format used for MCP tool input/output. Must be a format registered in api_platform.formats (e.g. "jsonld", "json", "jsonapi"). // Default: "jsonld"
 *     },
 *     exception_to_status?: array<string, int|Param>,
 *     formats?: array<string, array{ // Default: {"jsonld":{"mime_types":["application/ld+json"]}}
 *         mime_types?: list<scalar|Param|null>,
 *     }>,
 *     patch_formats?: array<string, array{ // Default: {"json":{"mime_types":["application/merge-patch+json"]}}
 *         mime_types?: list<scalar|Param|null>,
 *     }>,
 *     docs_formats?: array<string, array{ // Default: {"jsonld":{"mime_types":["application/ld+json"]},"jsonopenapi":{"mime_types":["application/vnd.openapi+json"]},"html":{"mime_types":["text/html"]},"yamlopenapi":{"mime_types":["application/vnd.openapi+yaml"]}}
 *         mime_types?: list<scalar|Param|null>,
 *     }>,
 *     error_formats?: array<string, array{ // Default: {"jsonld":{"mime_types":["application/ld+json"]},"jsonproblem":{"mime_types":["application/problem+json"]},"json":{"mime_types":["application/problem+json","application/json"]}}
 *         mime_types?: list<scalar|Param|null>,
 *     }>,
 *     jsonschema_formats?: list<scalar|Param|null>,
 *     defaults?: array{
 *         uri_template?: mixed,
 *         short_name?: mixed,
 *         description?: mixed,
 *         types?: mixed,
 *         operations?: mixed,
 *         formats?: mixed,
 *         input_formats?: mixed,
 *         output_formats?: mixed,
 *         uri_variables?: mixed,
 *         route_prefix?: mixed,
 *         defaults?: mixed,
 *         requirements?: mixed,
 *         options?: mixed,
 *         stateless?: mixed,
 *         sunset?: mixed,
 *         accept_patch?: mixed,
 *         status?: mixed,
 *         host?: mixed,
 *         schemes?: mixed,
 *         condition?: mixed,
 *         controller?: mixed,
 *         class?: mixed,
 *         url_generation_strategy?: mixed,
 *         deprecation_reason?: mixed,
 *         headers?: mixed,
 *         cache_headers?: mixed,
 *         normalization_context?: mixed,
 *         denormalization_context?: mixed,
 *         collect_denormalization_errors?: mixed,
 *         hydra_context?: mixed,
 *         openapi?: mixed,
 *         validation_context?: mixed,
 *         filters?: mixed,
 *         mercure?: mixed,
 *         messenger?: mixed,
 *         input?: mixed,
 *         output?: mixed,
 *         order?: mixed,
 *         fetch_partial?: mixed,
 *         force_eager?: mixed,
 *         pagination_client_enabled?: mixed,
 *         pagination_client_items_per_page?: mixed,
 *         pagination_client_partial?: mixed,
 *         pagination_via_cursor?: mixed,
 *         pagination_enabled?: mixed,
 *         pagination_fetch_join_collection?: mixed,
 *         pagination_use_output_walkers?: mixed,
 *         pagination_items_per_page?: mixed,
 *         pagination_maximum_items_per_page?: mixed,
 *         pagination_partial?: mixed,
 *         pagination_type?: mixed,
 *         security?: mixed,
 *         security_message?: mixed,
 *         security_post_denormalize?: mixed,
 *         security_post_denormalize_message?: mixed,
 *         security_post_validation?: mixed,
 *         security_post_validation_message?: mixed,
 *         composite_identifier?: mixed,
 *         exception_to_status?: mixed,
 *         query_parameter_validation_enabled?: mixed,
 *         links?: mixed,
 *         graph_ql_operations?: mixed,
 *         provider?: mixed,
 *         processor?: mixed,
 *         state_options?: mixed,
 *         rules?: mixed,
 *         policy?: mixed,
 *         middleware?: mixed,
 *         parameters?: array<string, array{ // Default: []
 *             key?: mixed,
 *             schema?: mixed,
 *             open_api?: mixed,
 *             provider?: mixed,
 *             filter?: mixed,
 *             property?: mixed,
 *             description?: mixed,
 *             properties?: mixed,
 *             required?: mixed,
 *             priority?: mixed,
 *             hydra?: mixed,
 *             constraints?: mixed,
 *             security?: mixed,
 *             security_message?: mixed,
 *             extra_properties?: mixed,
 *             filter_context?: mixed,
 *             native_type?: mixed,
 *             cast_to_array?: mixed,
 *             cast_to_native_type?: mixed,
 *             cast_fn?: mixed,
 *             default?: mixed,
 *             filter_class?: mixed,
 *             ...<string, mixed>
 *         }>,
 *         strict_query_parameter_validation?: mixed,
 *         hide_hydra_operation?: mixed,
 *         json_stream?: mixed,
 *         extra_properties?: mixed,
 *         map?: mixed,
 *         mcp?: mixed,
 *         route_name?: mixed,
 *         errors?: mixed,
 *         read?: mixed,
 *         deserialize?: mixed,
 *         validate?: mixed,
 *         write?: mixed,
 *         serialize?: mixed,
 *         content_negotiation?: mixed,
 *         priority?: mixed,
 *         name?: mixed,
 *         allow_create?: mixed,
 *         item_uri_template?: mixed,
 *         ...<string, mixed>
 *     },
 * }
 * @psalm-type SolidInvoicePaymentConfig = array{
 *     gateways?: list<array{ // Default: []
 *         name?: scalar|Param|null,
 *         factory?: scalar|Param|null,
 *         form?: scalar|Param|null,
 *     }>,
 * }
 * @psalm-type LiipTestFixturesConfig = array{
 *     cache_db?: array{
 *         sqlite?: scalar|Param|null, // Default: null
 *         ...<string, mixed>
 *     },
 *     keep_database_and_schema?: bool|Param, // Default: false
 *     cache_metadata?: bool|Param, // Default: true
 * }
 * @psalm-type DebugConfig = array{
 *     max_items?: int|Param, // Max number of displayed items past the first level, -1 means no limit. // Default: 2500
 *     min_depth?: int|Param, // Minimum tree depth to clone all the items, 1 is default. // Default: 1
 *     max_string_length?: int|Param, // Max length of displayed strings, -1 means no limit. // Default: -1
 *     dump_destination?: scalar|Param|null, // A stream URL where dumps should be written to. // Default: null
 *     theme?: "dark"|"light"|Param, // Changes the color of the dump() output when rendered directly on the templating. "dark" (default) or "light". // Default: "dark"
 * }
 * @psalm-type MakerConfig = array{
 *     root_namespace?: scalar|Param|null, // Default: "App"
 *     generate_final_classes?: bool|Param, // Default: true
 *     generate_final_entities?: bool|Param, // Default: false
 * }
 * @psalm-type WebProfilerConfig = array{
 *     toolbar?: bool|array{ // Profiler toolbar configuration
 *         enabled?: bool|Param, // Default: false
 *         ajax_replace?: bool|Param, // Replace toolbar on AJAX requests // Default: false
 *     },
 *     intercept_redirects?: bool|Param, // Default: false
 *     excluded_ajax_paths?: scalar|Param|null, // Default: "^/((index|app(_[\\w]+)?)\\.php/)?_wdt"
 * }
 * @psalm-type TogglerConfig = array{
 *     config?: array{
 *         storage?: scalar|Param|null, // Set the storage handler service // Default: null
 *         features?: array<string, scalar|Param|null>,
 *     },
 * }
 * @psalm-type ZenstruckFoundryConfig = array{
 *     auto_refresh_proxies?: bool|Param|null, // Deprecated: Since 2.0 auto_refresh_proxies defaults to true and this configuration has no effect. // Whether to auto-refresh proxies by default (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh) // Default: null
 *     enable_auto_refresh_with_lazy_objects?: bool|Param|null, // Enable auto-refresh using PHP 8.4 lazy objects (cannot be enabled if PHP < 8.4). // Default: null
 *     faker?: array{ // Configure the faker used by your factories.
 *         locale?: scalar|Param|null, // The default locale to use for faker. // Default: null
 *         seed?: scalar|Param|null, // Deprecated: The "faker.seed" configuration is deprecated and will be removed in 3.0. Use environment variable "FOUNDRY_FAKER_SEED" instead. // Random number generator seed to produce the same fake values every run. // Default: null
 *         manage_seed?: bool|Param, // Automatically manage faker seed to ensure consistent data between test runs. // Default: true
 *         service?: scalar|Param|null, // Service id for custom faker instance. // Default: null
 *     },
 *     instantiator?: array{ // Configure the default instantiator used by your object factories.
 *         use_constructor?: bool|Param, // Use the constructor to instantiate objects. // Default: true
 *         allow_extra_attributes?: bool|Param, // Whether or not to skip attributes that do not correspond to properties. // Default: false
 *         always_force_properties?: bool|Param, // Whether or not to skip setters and force set object properties (public/private/protected) directly. // Default: false
 *         service?: scalar|Param|null, // Service id of your custom instantiator. // Default: null
 *     },
 *     global_state?: list<scalar|Param|null>,
 *     persistence?: array{
 *         flush_once?: bool|Param, // Flush only once per call of `PersistentObjectFactory::create()` in userland. // Default: false
 *     },
 *     orm?: array{
 *         auto_persist?: bool|Param, // Deprecated: Since 2.4 auto_persist defaults to true and this configuration has no effect. // Automatically persist entities when created. // Default: true
 *         reset?: array{
 *             connections?: list<scalar|Param|null>,
 *             entity_managers?: list<scalar|Param|null>,
 *             mode?: \Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode::SCHEMA|\Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode::MIGRATE|Param, // Reset mode to use with ResetDatabase trait // Default: "schema"
 *             migrations?: array{
 *                 configurations?: list<scalar|Param|null>,
 *             },
 *         },
 *     },
 *     mongo?: array{
 *         auto_persist?: bool|Param, // Deprecated: Since 2.4 auto_persist defaults to true and this configuration has no effect. // Automatically persist documents when created. // Default: true
 *         reset?: array{
 *             document_managers?: list<scalar|Param|null>,
 *         },
 *     },
 *     make_factory?: array{
 *         default_namespace?: scalar|Param|null, // Default namespace where factories will be created by maker. // Default: "Factory"
 *         add_hints?: bool|Param, // Add "beginner" hints in the created factory. // Default: true
 *     },
 *     make_story?: array{
 *         default_namespace?: scalar|Param|null, // Default namespace where stories will be created by maker. // Default: "Story"
 *     },
 * }
 * @psalm-type SentryConfig = array{
 *     dsn?: scalar|Param|null, // If this value is not provided, the SDK will try to read it from the SENTRY_DSN environment variable. If that variable also does not exist, the SDK will not send any events.
 *     register_error_listener?: bool|Param, // Default: true
 *     register_error_handler?: bool|Param, // Default: true
 *     logger?: scalar|Param|null, // The service ID of the PSR-3 logger used to log messages coming from the SDK client. Be aware that setting the same logger of the application may create a circular loop when an event fails to be sent. // Default: null
 *     options?: array{
 *         integrations?: mixed, // Default: []
 *         default_integrations?: bool|Param,
 *         prefixes?: list<scalar|Param|null>,
 *         sample_rate?: float|Param, // The sampling factor to apply to events. A value of 0 will deny sending any event, and a value of 1 will send all events.
 *         enable_tracing?: bool|Param,
 *         traces_sample_rate?: float|Param, // The sampling factor to apply to transactions. A value of 0 will deny sending any transaction, and a value of 1 will send all transactions.
 *         traces_sampler?: scalar|Param|null,
 *         profiles_sample_rate?: float|Param, // The sampling factor to apply to profiles. A value of 0 will deny sending any profiles, and a value of 1 will send all profiles. Profiles are sampled in relation to traces_sample_rate
 *         enable_logs?: bool|Param,
 *         log_flush_threshold?: mixed, // Default: null
 *         enable_metrics?: bool|Param, // Default: true
 *         attach_stacktrace?: bool|Param,
 *         attach_metric_code_locations?: bool|Param,
 *         context_lines?: int|Param,
 *         environment?: scalar|Param|null, // Default: "%kernel.environment%"
 *         logger?: scalar|Param|null,
 *         spotlight?: bool|Param,
 *         spotlight_url?: scalar|Param|null,
 *         release?: scalar|Param|null, // Default: "%env(default::SENTRY_RELEASE)%"
 *         org_id?: int|Param,
 *         server_name?: scalar|Param|null,
 *         ignore_exceptions?: list<scalar|Param|null>,
 *         ignore_transactions?: list<scalar|Param|null>,
 *         before_send?: scalar|Param|null,
 *         before_send_transaction?: scalar|Param|null,
 *         before_send_check_in?: scalar|Param|null,
 *         before_send_metrics?: scalar|Param|null,
 *         before_send_log?: scalar|Param|null,
 *         before_send_metric?: scalar|Param|null,
 *         trace_propagation_targets?: mixed,
 *         strict_trace_continuation?: bool|Param,
 *         tags?: array<string, scalar|Param|null>,
 *         error_types?: scalar|Param|null,
 *         max_breadcrumbs?: int|Param,
 *         before_breadcrumb?: mixed,
 *         in_app_exclude?: list<scalar|Param|null>,
 *         in_app_include?: list<scalar|Param|null>,
 *         send_default_pii?: bool|Param,
 *         max_value_length?: int|Param,
 *         transport?: scalar|Param|null,
 *         http_client?: scalar|Param|null,
 *         http_proxy?: scalar|Param|null,
 *         http_proxy_authentication?: scalar|Param|null,
 *         http_connect_timeout?: float|Param, // The maximum number of seconds to wait while trying to connect to a server. It works only when using the default transport.
 *         http_timeout?: float|Param, // The maximum execution time for the request+response as a whole. It works only when using the default transport.
 *         http_ssl_verify_peer?: bool|Param,
 *         http_compression?: bool|Param,
 *         capture_silenced_errors?: bool|Param,
 *         max_request_body_size?: "none"|"never"|"small"|"medium"|"always"|Param,
 *         class_serializers?: array<string, scalar|Param|null>,
 *     },
 *     messenger?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         capture_soft_fails?: bool|Param, // Default: true
 *         isolate_breadcrumbs_by_message?: bool|Param, // Default: false
 *         isolate_context_by_message?: bool|Param, // Default: false
 *     },
 *     tracing?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         dbal?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *             ignore_prepare_spans?: bool|Param, // Default: false
 *             connections?: list<scalar|Param|null>,
 *         },
 *         twig?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *         },
 *         cache?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *         },
 *         http_client?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *         },
 *         console?: array{
 *             excluded_commands?: list<scalar|Param|null>,
 *         },
 *     },
 * }
 * @psalm-type StimulusConfig = array{
 *     controller_paths?: list<scalar|Param|null>,
 *     controllers_json?: scalar|Param|null, // Default: "%kernel.project_dir%/assets/controllers.json"
 * }
 * @psalm-type TwigComponentConfig = array{
 *     defaults?: array<string, string|array{ // Default: []
 *         template_directory?: scalar|Param|null, // Default: "components"
 *         name_prefix?: scalar|Param|null, // Default: ""
 *     }>,
 *     anonymous_template_directory?: scalar|Param|null, // Defaults to `components`
 *     profiler?: bool|array{ // Enables the profiler for Twig Component
 *         enabled?: bool|Param, // Default: "%kernel.debug%"
 *         collect_components?: bool|Param, // Collect components instances // Default: true
 *     },
 * }
 * @psalm-type LiveComponentConfig = array{
 *     secret?: scalar|Param|null, // The secret used to compute fingerprints and checksums // Default: "%kernel.secret%"
 *     fetch_credentials?: "same-origin"|"include"|"omit"|Param, // The default fetch credentials mode for all Live Components ('same-origin', 'include', 'omit') // Default: "same-origin"
 * }
 * @psalm-type BabdevPagerfantaConfig = array{
 *     default_view?: scalar|Param|null, // Default: "default"
 *     default_twig_template?: scalar|Param|null, // Default: "@BabDevPagerfanta/default.html.twig"
 *     exceptions_strategy?: array{
 *         out_of_range_page?: "to_http_not_found"|"custom"|Param, // Default: "to_http_not_found"
 *         not_valid_current_page?: "to_http_not_found"|"custom"|Param, // Default: "to_http_not_found"
 *     },
 * }
 * @psalm-type SymfonycastsVerifyEmailConfig = array{
 *     lifetime?: int|Param, // The length of time in seconds that a signed URI is valid for after it is created. // Default: 3600
 * }
 * @psalm-type KnpuOauth2ClientConfig = array{
 *     http_client?: scalar|Param|null, // Service id of HTTP client to use (must implement GuzzleHttp\ClientInterface) // Default: null
 *     http_client_options?: array{
 *         timeout?: int|Param,
 *         proxy?: scalar|Param|null,
 *         verify?: bool|Param, // Use only with proxy option set
 *     },
 *     clients?: array<string, array<string, mixed>>,
 * }
 * @psalm-type SymfonycastsResetPasswordConfig = array{
 *     request_password_repository?: scalar|Param|null, // A class that implements ResetPasswordRequestRepositoryInterface - usually your ResetPasswordRequestRepository.
 *     lifetime?: int|Param, // The length of time in seconds that a password reset request is valid for after it is created. // Default: 3600
 *     throttle_limit?: int|Param, // Another password reset cannot be made faster than this throttle time in seconds. // Default: 3600
 *     enable_garbage_collection?: bool|Param, // Enable/Disable automatic garbage collection. // Default: true
 * }
 * @psalm-type MeilisearchConfig = array{
 *     url?: scalar|Param|null, // Default: "http://localhost:7700"
 *     api_key?: scalar|Param|null,
 *     prefix?: scalar|Param|null, // Default: null
 *     nbResults?: int|Param, // Default: 20
 *     batchSize?: int|Param, // Default: 500
 *     doctrineSubscribedEvents?: list<scalar|Param|null>,
 *     serializer?: scalar|Param|null, // Default: "serializer"
 *     http_client?: scalar|Param|null, // Default: "psr18.http_client"
 *     indices?: list<array{ // Default: []
 *         name?: scalar|Param|null,
 *         class?: scalar|Param|null,
 *         type?: "orm"|"custom"|Param, // Default: "orm"
 *         primary_key?: scalar|Param|null, // Default: "objectID"
 *         data_provider?: scalar|Param|null, // Default: null
 *         id_normalizer?: scalar|Param|null, // Default: "meilisearch.identifier.default_id_normalizer"
 *         enable_serializer_groups?: bool|Param, // When set to true, it will call normalize method with an extra groups parameter "groups" => [SearchableObject::NORMALIZATION_GROUP] // Default: false
 *         serializer_groups?: list<scalar|Param|null>,
 *         index_if?: scalar|Param|null, // Property accessor path (like method or property name) used to decide if an entry should be indexed. // Default: null
 *         settings?: mixed, // Configure indices settings, see: https://www.meilisearch.com/docs/reference/api/settings // Default: []
 *     }>,
 * }
 * @psalm-type McpConfig = array{
 *     app?: scalar|Param|null, // Default: "app"
 *     version?: scalar|Param|null, // Default: "0.0.1"
 *     description?: scalar|Param|null, // Default: null
 *     icons?: list<array{ // Default: []
 *         src?: scalar|Param|null,
 *         mime_type?: scalar|Param|null, // Default: null
 *         sizes?: list<scalar|Param|null>,
 *     }>,
 *     website_url?: scalar|Param|null, // Default: null
 *     pagination_limit?: int|Param, // Default: 50
 *     instructions?: scalar|Param|null, // Default: null
 *     client_transports?: array{
 *         stdio?: bool|Param, // Default: false
 *         http?: bool|Param, // Default: false
 *     },
 *     discovery?: array{
 *         scan_dirs?: list<scalar|Param|null>,
 *         exclude_dirs?: list<scalar|Param|null>,
 *     },
 *     http?: array{
 *         path?: scalar|Param|null, // Default: "/_mcp"
 *         session?: array{
 *             store?: "file"|"memory"|"cache"|"framework"|Param, // Default: "file"
 *             directory?: scalar|Param|null, // Default: "%kernel.cache_dir%/mcp-sessions"
 *             cache_pool?: scalar|Param|null, // Default: "cache.mcp.sessions"
 *             prefix?: scalar|Param|null, // Default: "mcp-"
 *             ttl?: int|Param, // Default: 3600
 *         },
 *     },
 * }
 * @psalm-type SchebTwoFactorConfig = array{
 *     persister?: scalar|Param|null, // Default: "scheb_two_factor.persister.doctrine"
 *     model_manager_name?: scalar|Param|null, // Default: null
 *     security_tokens?: list<scalar|Param|null>,
 *     ip_whitelist?: list<scalar|Param|null>,
 *     ip_whitelist_provider?: scalar|Param|null, // Default: "scheb_two_factor.default_ip_whitelist_provider"
 *     two_factor_token_factory?: scalar|Param|null, // Default: "scheb_two_factor.default_token_factory"
 *     two_factor_provider_decider?: scalar|Param|null, // Default: "scheb_two_factor.default_provider_decider"
 *     two_factor_condition?: scalar|Param|null, // Default: null
 *     code_reuse_cache?: scalar|Param|null, // Default: null
 *     code_reuse_cache_duration?: int|Param, // Default: 60
 *     code_reuse_default_handler?: scalar|Param|null, // Default: null
 *     trusted_device?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         manager?: scalar|Param|null, // Default: "scheb_two_factor.default_trusted_device_manager"
 *         lifetime?: int|Param, // Default: 5184000
 *         extend_lifetime?: bool|Param, // Default: false
 *         key?: scalar|Param|null, // Default: null
 *         cookie_name?: scalar|Param|null, // Default: "trusted_device"
 *         cookie_secure?: true|false|"auto"|Param, // Default: "auto"
 *         cookie_domain?: scalar|Param|null, // Default: null
 *         cookie_path?: scalar|Param|null, // Default: "/"
 *         cookie_same_site?: scalar|Param|null, // Default: "lax"
 *     },
 *     backup_codes?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         manager?: scalar|Param|null, // Default: "scheb_two_factor.default_backup_code_manager"
 *     },
 *     email?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         mailer?: scalar|Param|null, // Default: null
 *         code_generator?: scalar|Param|null, // Default: "scheb_two_factor.security.email.default_code_generator"
 *         form_renderer?: scalar|Param|null, // Default: null
 *         sender_email?: scalar|Param|null, // Default: null
 *         sender_name?: scalar|Param|null, // Default: null
 *         template?: scalar|Param|null, // Default: "@SchebTwoFactor/Authentication/form.html.twig"
 *         digits?: int|Param, // Default: 4
 *     },
 *     totp?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         form_renderer?: scalar|Param|null, // Default: null
 *         issuer?: scalar|Param|null, // Default: null
 *         server_name?: scalar|Param|null, // Default: null
 *         leeway?: int|Param, // Default: 0
 *         parameters?: list<scalar|Param|null>,
 *         template?: scalar|Param|null, // Default: "@SchebTwoFactor/Authentication/form.html.twig"
 *     },
 * }
 * @psalm-type TwigExtraConfig = array{
 *     cache?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *     },
 *     html?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     markdown?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     intl?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *     },
 *     cssinliner?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     inky?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     string?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     commonmark?: array{
 *         renderer?: array{ // Array of options for rendering HTML.
 *             block_separator?: scalar|Param|null,
 *             inner_separator?: scalar|Param|null,
 *             soft_break?: scalar|Param|null,
 *         },
 *         html_input?: "strip"|"allow"|"escape"|Param, // How to handle HTML input.
 *         allow_unsafe_links?: bool|Param, // Remove risky link and image URLs by setting this to false. // Default: true
 *         max_nesting_level?: int|Param, // The maximum nesting level for blocks. // Default: 9223372036854775807
 *         max_delimiters_per_line?: int|Param, // The maximum number of strong/emphasis delimiters per line. // Default: 9223372036854775807
 *         slug_normalizer?: array{ // Array of options for configuring how URL-safe slugs are created.
 *             instance?: mixed,
 *             max_length?: int|Param, // Default: 255
 *             unique?: mixed,
 *         },
 *         commonmark?: array{ // Array of options for configuring the CommonMark core extension.
 *             enable_em?: bool|Param, // Default: true
 *             enable_strong?: bool|Param, // Default: true
 *             use_asterisk?: bool|Param, // Default: true
 *             use_underscore?: bool|Param, // Default: true
 *             unordered_list_markers?: list<scalar|Param|null>,
 *         },
 *         ...<string, mixed>
 *     },
 * }
 * @psalm-type KnpMenuConfig = array{
 *     providers?: array{
 *         builder_alias?: bool|Param, // Default: true
 *     },
 *     twig?: array{
 *         template?: scalar|Param|null, // Default: "@KnpMenu/menu.html.twig"
 *     },
 *     templating?: bool|Param, // Default: false
 *     default_renderer?: scalar|Param|null, // Default: "twig"
 * }
 * @psalm-type UxIconsConfig = array{
 *     icon_dir?: scalar|Param|null, // The local directory where icons are stored. // Default: "%kernel.project_dir%/assets/icons"
 *     default_icon_attributes?: array<string, scalar|Param|null>,
 *     icon_sets?: array<string, array{ // the icon set prefix (e.g. "acme") // Default: []
 *         path?: scalar|Param|null, // The local icon set directory path. (cannot be used with 'alias')
 *         alias?: scalar|Param|null, // The remote icon set identifier. (cannot be used with 'path')
 *         icon_attributes?: array<string, scalar|Param|null>,
 *         suffixes?: array<string, array{ // The suffix name (e.g. "solid", "20-solid") // Default: []
 *             icon_attributes?: array<string, scalar|Param|null>,
 *         }>,
 *     }>,
 *     aliases?: array<string, string|Param>,
 *     iconify?: bool|array{ // Configuration for the remote icon service.
 *         enabled?: bool|Param, // Default: true
 *         on_demand?: bool|Param, // Whether to download icons "on demand". // Default: true
 *         endpoint?: scalar|Param|null, // The endpoint for the Iconify icons API. // Default: "https://api.iconify.design"
 *     },
 *     ignore_not_found?: bool|Param, // Ignore error when an icon is not found. Set to 'true' to fail silently. // Default: false
 * }
 * @psalm-type PaymentConfig = array{
 *     gateways?: list<array{ // Default: []
 *         name?: scalar|Param|null,
 *         factory?: scalar|Param|null,
 *         form?: scalar|Param|null,
 *     }>,
 * }
 * @psalm-type ConfigType = array{
 *     imports?: ImportsConfig,
 *     parameters?: ParametersConfig,
 *     services?: ServicesConfig,
 *     framework?: FrameworkConfig,
 *     security?: SecurityConfig,
 *     twig?: TwigConfig,
 *     monolog?: MonologConfig,
 *     doctrine?: DoctrineConfig,
 *     webpack_encore?: WebpackEncoreConfig,
 *     doctrine_migrations?: DoctrineMigrationsConfig,
 *     payum?: PayumConfig,
 *     stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *     api_platform?: ApiPlatformConfig,
 *     solid_invoice_payment?: SolidInvoicePaymentConfig,
 *     liip_test_fixtures?: LiipTestFixturesConfig,
 *     web_profiler?: WebProfilerConfig,
 *     toggler?: TogglerConfig,
 *     zenstruck_foundry?: ZenstruckFoundryConfig,
 *     sentry?: SentryConfig,
 *     stimulus?: StimulusConfig,
 *     twig_component?: TwigComponentConfig,
 *     live_component?: LiveComponentConfig,
 *     babdev_pagerfanta?: BabdevPagerfantaConfig,
 *     symfonycasts_verify_email?: SymfonycastsVerifyEmailConfig,
 *     knpu_oauth2_client?: KnpuOauth2ClientConfig,
 *     symfonycasts_reset_password?: SymfonycastsResetPasswordConfig,
 *     meilisearch?: MeilisearchConfig,
 *     mcp?: McpConfig,
 *     scheb_two_factor?: SchebTwoFactorConfig,
 *     twig_extra?: TwigExtraConfig,
 *     knp_menu?: KnpMenuConfig,
 *     ux_icons?: UxIconsConfig,
 *     payment?: PaymentConfig,
 *     "when@dev"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         security?: SecurityConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         doctrine?: DoctrineConfig,
 *         webpack_encore?: WebpackEncoreConfig,
 *         doctrine_migrations?: DoctrineMigrationsConfig,
 *         payum?: PayumConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         api_platform?: ApiPlatformConfig,
 *         solid_invoice_payment?: SolidInvoicePaymentConfig,
 *         liip_test_fixtures?: LiipTestFixturesConfig,
 *         debug?: DebugConfig,
 *         maker?: MakerConfig,
 *         web_profiler?: WebProfilerConfig,
 *         toggler?: TogglerConfig,
 *         zenstruck_foundry?: ZenstruckFoundryConfig,
 *         sentry?: SentryConfig,
 *         stimulus?: StimulusConfig,
 *         twig_component?: TwigComponentConfig,
 *         live_component?: LiveComponentConfig,
 *         babdev_pagerfanta?: BabdevPagerfantaConfig,
 *         symfonycasts_verify_email?: SymfonycastsVerifyEmailConfig,
 *         knpu_oauth2_client?: KnpuOauth2ClientConfig,
 *         symfonycasts_reset_password?: SymfonycastsResetPasswordConfig,
 *         meilisearch?: MeilisearchConfig,
 *         mcp?: McpConfig,
 *     },
 *     "when@test"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         security?: SecurityConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         doctrine?: DoctrineConfig,
 *         webpack_encore?: WebpackEncoreConfig,
 *         doctrine_migrations?: DoctrineMigrationsConfig,
 *         payum?: PayumConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         api_platform?: ApiPlatformConfig,
 *         solid_invoice_payment?: SolidInvoicePaymentConfig,
 *         liip_test_fixtures?: LiipTestFixturesConfig,
 *         web_profiler?: WebProfilerConfig,
 *         toggler?: TogglerConfig,
 *         zenstruck_foundry?: ZenstruckFoundryConfig,
 *         sentry?: SentryConfig,
 *         stimulus?: StimulusConfig,
 *         twig_component?: TwigComponentConfig,
 *         live_component?: LiveComponentConfig,
 *         babdev_pagerfanta?: BabdevPagerfantaConfig,
 *         symfonycasts_verify_email?: SymfonycastsVerifyEmailConfig,
 *         knpu_oauth2_client?: KnpuOauth2ClientConfig,
 *         symfonycasts_reset_password?: SymfonycastsResetPasswordConfig,
 *         meilisearch?: MeilisearchConfig,
 *         mcp?: McpConfig,
 *     },
 *     ...<string, ExtensionType|array{ // extra keys must follow the when@%env% pattern or match an extension alias
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         ...<string, ExtensionType>,
 *     }>
 * }
 */
final class App
{
    /**
     * @param ConfigType $config
     *
     * @psalm-return ConfigType
     */
    public static function config(array $config): array
    {
        /** @var ConfigType $config */
        $config = AppReference::config($config);

        return $config;
    }
}

namespace Symfony\Component\Routing\Loader\Configurator;

/**
 * This class provides array-shapes for configuring the routes of an application.
 *
 * Example:
 *
 *     ```php
 *     // config/routes.php
 *     namespace Symfony\Component\Routing\Loader\Configurator;
 *
 *     return Routes::config([
 *         'controllers' => [
 *             'resource' => 'routing.controllers',
 *         ],
 *     ]);
 *     ```
 *
 * @psalm-type RouteConfig = array{
 *     path: string|array<string,string>,
 *     controller?: string,
 *     methods?: string|list<string>,
 *     requirements?: array<string,string>,
 *     defaults?: array<string,mixed>,
 *     options?: array<string,mixed>,
 *     host?: string|array<string,string>,
 *     schemes?: string|list<string>,
 *     condition?: string,
 *     locale?: string,
 *     format?: string,
 *     utf8?: bool,
 *     stateless?: bool,
 * }
 * @psalm-type ImportConfig = array{
 *     resource: string,
 *     type?: string,
 *     exclude?: string|list<string>,
 *     prefix?: string|array<string,string>,
 *     name_prefix?: string,
 *     trailing_slash_on_root?: bool,
 *     controller?: string,
 *     methods?: string|list<string>,
 *     requirements?: array<string,string>,
 *     defaults?: array<string,mixed>,
 *     options?: array<string,mixed>,
 *     host?: string|array<string,string>,
 *     schemes?: string|list<string>,
 *     condition?: string,
 *     locale?: string,
 *     format?: string,
 *     utf8?: bool,
 *     stateless?: bool,
 * }
 * @psalm-type AliasConfig = array{
 *     alias: string,
 *     deprecated?: array{package:string, version:string, message?:string},
 * }
 * @psalm-type RoutesConfig = array{
 *     "when@dev"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     "when@test"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     ...<string, RouteConfig|ImportConfig|AliasConfig>
 * }
 */
final class Routes
{
    /**
     * @param RoutesConfig $config
     *
     * @psalm-return RoutesConfig
     */
    public static function config(array $config): array
    {
        return $config;
    }
}
