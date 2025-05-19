<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Action\\\\Index\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Action/Index.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Form\\\\ConstraintBuilder\\:\\:build\\(\\) has parameter \\$options with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Form/ConstraintBuilder.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Form\\\\ConstraintBuilder\\:\\:build\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Form/ConstraintBuilder.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$default of method SolidWorx\\\\FormHandler\\\\Options\\:\\:get\\(\\) expects null, SolidInvoice\\\\ClientBundle\\\\Entity\\\\Client given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Form/Handler/AbstractClientFormHandler.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Form\\\\Handler\\\\AbstractContactFormHandler\\:\\:serialize\\(\\) has parameter \\$groups with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Form/Handler/AbstractContactFormHandler.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\ClientBundle\\\\Repository\\\\ContactRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Repository/ContactRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\ClientBundle\\\\Repository\\\\ContactTypeRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Repository/ContactTypeRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Tests\\\\Form\\\\Handler\\\\ClientEditFormHandlerTest\\:\\:getFormData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Tests/Form/Handler/ClientEditFormHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Tests\\\\Form\\\\Handler\\\\ClientEditFormHandlerTest\\:\\:getHandlerOptions\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Tests/Form/Handler/ClientEditFormHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Tests\\\\Form\\\\Handler\\\\ContactAddFormHandlerTest\\:\\:getFormData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Tests/Form/Handler/ContactAddFormHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Tests\\\\Form\\\\Handler\\\\ContactEditFormHandlerTest\\:\\:getFormData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Tests/Form/Handler/ContactEditFormHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\ClientBundle\\\\Tests\\\\Form\\\\Handler\\\\ContactEditFormHandlerTest\\:\\:getHandlerOptions\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ClientBundle/Tests/Form/Handler/ContactEditFormHandlerTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Billing\\\\TotalCalculator\\:\\:calculateTotals\\(\\) has parameter \\$entity with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Billing/TotalCalculator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Billing\\\\TotalCalculator\\:\\:updateTotal\\(\\) has parameter \\$entity with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Billing/TotalCalculator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\CoreBundle\\\\Form\\\\Transformer\\\\DiscountTransformer implements generic interface Symfony\\\\Component\\\\Form\\\\DataTransformerInterface but does not specify its types\\: T, R$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Form/Transformer/DiscountTransformer.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\HttpFoundation\\\\Session\\\\SessionInterface\\:\\:getFlashBag\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Listener/SessionRequestListener.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Logger\\\\Dbal\\\\TraceLogger\\:\\:formatTrace\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Logger/Dbal/TraceLogger.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Logger\\\\Dbal\\\\TraceLogger\\:\\:formatTrace\\(\\) has parameter \\$trace with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Logger/Dbal/TraceLogger.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Logger\\\\Dbal\\\\TraceLogger\\:\\:getBactrace\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Logger/Dbal/TraceLogger.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\CoreBundle\\\\Logger\\\\Dbal\\\\TraceLogger\\:\\:\\$queries type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Logger/Dbal/TraceLogger.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Model\\\\Status\\:\\:getStatusList\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Model/Status.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\CoreBundle\\\\Model\\\\Status\\:\\:\\$statusLabels type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Model/Status.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\CoreBundle\\\\Repository\\\\VersionRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Repository/VersionRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Response\\\\PdfResponse\\:\\:__construct\\(\\) has parameter \\$headers with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Response/PdfResponse.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Templating\\\\Template\\:\\:__construct\\(\\) has parameter \\$params with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Templating/Template.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\CoreBundle\\\\Templating\\\\Template\\:\\:getParams\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Templating/Template.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property SolidInvoice\\\\CoreBundle\\\\Tests\\\\KernelAwareTest\\:\\:\\$container \\(Symfony\\\\Component\\\\DependencyInjection\\\\Container\\) does not accept Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CoreBundle/Tests/KernelAwareTest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\DashboardBundle\\\\Widgets\\\\RecentClientsWidget\\:\\:getData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DashboardBundle/Widgets/RecentClientsWidget.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\DashboardBundle\\\\Widgets\\\\RecentInvoicesWidget\\:\\:getData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DashboardBundle/Widgets/RecentInvoicesWidget.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\DashboardBundle\\\\Widgets\\\\RecentPaymentsWidget\\:\\:getData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DashboardBundle/Widgets/RecentPaymentsWidget.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\DashboardBundle\\\\Widgets\\\\RecentQuotesWidget\\:\\:getData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DashboardBundle/Widgets/RecentQuotesWidget.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\DashboardBundle\\\\Widgets\\\\WidgetInterface\\:\\:getData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DashboardBundle/Widgets/WidgetInterface.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\CloneInvoice\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/CloneInvoice.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\CloneRecurringInvoice\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/CloneRecurringInvoice.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\Fields\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/Fields.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\Index\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/Index.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\RecurringIndex\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/RecurringIndex.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\InvoiceBundle\\\\Action\\\\RecurringTransition\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Action/RecurringTransition.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#1 \\$invoice of class SolidInvoice\\\\InvoiceBundle\\\\Email\\\\InvoiceEmail constructor expects SolidInvoice\\\\InvoiceBundle\\\\Entity\\\\Invoice, SolidInvoice\\\\InvoiceBundle\\\\Entity\\\\BaseInvoice given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/InvoiceBundle/Listener/Mailer/InvoiceMailerListener.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\ConfiguratorInterface\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/ConfiguratorInterface.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\GmailConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/GmailConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\MailchimpConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/MailchimpConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\MailgunConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/MailgunConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\PostmarkConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/PostmarkConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\SendgridConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/SendgridConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\MailerBundle\\\\Configurator\\\\SesConfigurator\\:\\:configure\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailerBundle/Configurator/SesConfigurator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\MoneyBundle\\\\Calculator\\:\\:calculateDiscount\\(\\) has parameter \\$entity with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MoneyBundle/Calculator.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\MoneyBundle\\\\Form\\\\DataTransformer\\\\ViewTransformer implements generic interface Symfony\\\\Component\\\\Form\\\\DataTransformerInterface but does not specify its types\\: T, R$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MoneyBundle/Form/DataTransformer/ViewTransformer.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Action\\\\Settings\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Action/Settings.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Exception\\\\InvalidGatewayException\\:\\:__construct\\(\\) has parameter \\$gateway with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Exception/InvalidGatewayException.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Factory\\\\PaymentFactories\\:\\:getFactories\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Factory/PaymentFactories.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Factory\\\\PaymentFactories\\:\\:setGatewayFactories\\(\\) has parameter \\$factories with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Factory/PaymentFactories.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Factory\\\\PaymentFactories\\:\\:setGatewayForms\\(\\) has parameter \\$gateForms with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Factory/PaymentFactories.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\PaymentBundle\\\\Factory\\\\PaymentFactories\\:\\:\\$factories type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Factory/PaymentFactories.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\PaymentBundle\\\\Factory\\\\PaymentFactories\\:\\:\\$forms type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Factory/PaymentFactories.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Form\\\\Type\\\\PaymentMethodSettingsType\\:\\:getOptions\\(\\) has parameter \\$settings with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Form/Type/PaymentMethodSettingsType.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Form\\\\Type\\\\PaymentMethodSettingsType\\:\\:getOptions\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Form/Type/PaymentMethodSettingsType.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Form\\\\Type\\\\PaymentMethodSettingsType\\:\\:getType\\(\\) has parameter \\$type with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Form/Type/PaymentMethodSettingsType.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Doctrine\\\\Persistence\\\\ObjectRepository\\:\\:getSettingsForMethodArray\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Manager/PaymentSettingsManager.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Manager\\\\PaymentSettingsManager\\:\\:get\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Manager/PaymentSettingsManager.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Property SolidInvoice\\\\PaymentBundle\\\\Manager\\\\PaymentSettingsManager\\:\\:\\$repository with generic interface Doctrine\\\\Persistence\\\\ObjectRepository does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Manager/PaymentSettingsManager.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\PaymentBundle\\\\Manager\\\\PaymentSettingsManager\\:\\:\\$settings type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Manager/PaymentSettingsManager.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Payum\\\\Core\\\\Request\\\\BaseGetStatus\\:\\:\\$status \\(int\\) does not accept string\\.$#',
	'count' => 10,
	'path' => __DIR__ . '/src/PaymentBundle/PaymentAction/Request/StatusRequest.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Payum\\\\Extension\\\\UpdatePaymentDetailsExtension\\:\\:onException\\(\\) has parameter \\$request with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Payum/Extension/UpdatePaymentDetailsExtension.php',
];
$ignoreErrors[] = [
	// identifier: missingType.parameter
	'message' => '#^Method SolidInvoice\\\\PaymentBundle\\\\Payum\\\\Extension\\\\UpdatePaymentDetailsExtension\\:\\:onReply\\(\\) has parameter \\$request with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PaymentBundle/Payum/Extension/UpdatePaymentDetailsExtension.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\QuoteBundle\\\\Action\\\\CloneQuote\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QuoteBundle/Action/CloneQuote.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\QuoteBundle\\\\Action\\\\Index\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QuoteBundle/Action/Index.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:add\\(\\) has parameter \\$settings with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:getSections\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:getSettings\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:\\$current \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:\\$elements type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Property SolidInvoice\\\\SettingsBundle\\\\Collection\\\\ConfigCollection\\:\\:\\$sections type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Collection/ConfigCollection.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\SettingsBundle\\\\Repository\\\\SectionRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Repository/SectionRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\SettingsBundle\\\\Repository\\\\SectionRepository\\:\\:getTopLevelSections\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Repository/SectionRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\SettingsBundle\\\\Repository\\\\SettingsRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Repository/SettingsRepository.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method SolidInvoice\\\\SettingsBundle\\\\Twig\\\\Components\\\\Settings\\:\\:getDataModelValue\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SettingsBundle/Twig/Components/Settings.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\TaxBundle\\\\Action\\\\Add\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Action/Add.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\TaxBundle\\\\Action\\\\Edit\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Action/Edit.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\TaxBundle\\\\Action\\\\Index\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Action/Index.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$default of method SolidWorx\\\\FormHandler\\\\Options\\:\\:get\\(\\) expects null, SolidInvoice\\\\TaxBundle\\\\Entity\\\\Tax given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Form/Handler/TaxFormHandler.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\TaxBundle\\\\Repository\\\\TaxRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Repository/TaxRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\TaxBundle\\\\Repository\\\\TaxRepository\\:\\:getTaxList\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TaxBundle/Repository/TaxRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\UserBundle\\\\Action\\\\Security\\\\ChangePassword\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Action/Security/ChangePassword.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method SolidInvoice\\\\UserBundle\\\\Action\\\\Security\\\\Login\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Action/Security/Login.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$default of method SolidWorx\\\\FormHandler\\\\Options\\:\\:get\\(\\) expects null, string given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Form/Handler/PasswordChangeHandler.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$default of method SolidWorx\\\\FormHandler\\\\Options\\:\\:get\\(\\) expects null, true given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Form/Handler/PasswordChangeHandler.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Class SolidInvoice\\\\UserBundle\\\\Repository\\\\ApiTokenRepository extends generic class Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\ServiceEntityRepository but does not specify its types\\: TEntityClass$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Repository/ApiTokenRepository.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method SolidInvoice\\\\UserBundle\\\\Tests\\\\Form\\\\Handler\\\\ProfileEditHandlerTest\\:\\:getFormData\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/UserBundle/Tests/Form/Handler/ProfileEditHandlerTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
