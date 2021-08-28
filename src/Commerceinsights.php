<?php
/**
 * Commerce Insights plugin for Craft CMS 3.x
 *
 * Get the low down on how your Commerce is doing
 *
 * @link      http://www.disposition.tools/
 * @copyright Copyright (c) 2021 Disposition Tools
 */

namespace dispositiontools\commerceinsights;

use dispositiontools\commerceinsights\services\Transactions as TransactionsService;
use dispositiontools\commerceinsights\services\Orders as OrdersService;
use dispositiontools\commerceinsights\services\Products as ProductsService;
use dispositiontools\commerceinsights\services\Customers as CustomersService;
use dispositiontools\commerceinsights\variables\CommerceinsightsVariable;
use dispositiontools\commerceinsights\models\Settings;
/*
use dispositiontools\commerceinsights\widgets\Transactions as TransactionsWidget;
use dispositiontools\commerceinsights\widgets\Orders as OrdersWidget;
use dispositiontools\commerceinsights\widgets\Products as ProductsWidget;
use dispositiontools\commerceinsights\widgets\Customers as CustomersWidget;
*/
use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use craft\events\RegisterCpNavItemsEvent;

use yii\base\Event;

use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Disposition Tools
 * @package   Commerceinsights
 * @since     1.0.0
 *
 * @property  TransactionsService $transactions
 * @property  OrdersService $orders
 * @property  ProductsService $products
 * @property  CustomersService $customers
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Commerceinsights extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Commerceinsights::$plugin
     *
     * @var Commerceinsights
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Commerceinsights::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'dispositiontools\commerceinsights\console\controllers';
        }
/*
        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'commerceinsights/cp';
                $event->rules['siteActionTrigger2'] = 'commerceinsights/reports';
            }
        );
*/
/*
        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'commerceinsights/cp/do-something';
                $event->rules['cpActionTrigger2'] = 'commerceinsights/reports/do-something';
            }
        );
*/
        // Register our widgets
        /*
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = TransactionsWidget::class;
                $event->types[] = OrdersWidget::class;
                $event->types[] = ProductsWidget::class;
                $event->types[] = CustomersWidget::class;
            }
        );
        */
        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('commerceinsights', CommerceinsightsVariable::class);
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );



        // adding Custom permissions

         Event::on(
             UserPermissions::class,
             UserPermissions::EVENT_REGISTER_PERMISSIONS,
             function(RegisterUserPermissionsEvent $event) {


               $commerceInsightPermissions = array();

               $commerceInsightPermissions[ 'commerceinsightsTransaction'] = array(
                      'label' => 'View transactions'
                  );
                $commerceInsightPermissions[ 'commerceinsightsTransactionDownload'] = array(
                       'label' => 'Download transactions'
                   );



               $commerceInsightPermissions[ 'commerceinsightsCustomers'] = array(
                      'label' => 'View customer summaries'
                  );
                $commerceInsightPermissions[ 'commerceinsightsCustomersDownload'] = array(
                       'label' => 'Download customer summaries'
                   );



               $commerceInsightPermissions[ 'commerceinsightsProducts'] = array(
                      'label' => 'View product sales'
                  );
                $commerceInsightPermissions[ 'commerceinsightsProductsDownload'] = array(
                       'label' => 'Download product sales'
                   );



                 // return those permissions
                 $event->permissions[ 'CommerceInsights']  = [
                   'commerceinsightsAccessModule' => [
                       'label' => 'Access Commerce Insights',
                       'nested' => $commerceInsightPermissions
                    ]
                 ];

         });


          $this->initRoutes();

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'commerceinsights',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }


    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();


        $parent['label'] = 'Commerce Insights';



        $craftCommercePlugin = Craft::$app->plugins->getPlugin('commerce', false);

        if ($craftCommercePlugin && $craftCommercePlugin != null && $craftCommercePlugin->isInstalled)
        {
            if (Craft::$app->user->checkPermission('commerceinsightsProducts')){
                $parent['subnav']['products'] = [
                    'label' =>  'Products',
                    'url' => 'commerceinsights/products'
                ];
            }

            if (Craft::$app->user->checkPermission('commerceinsightsCustomers')){
                $parent['subnav']['customers'] = [
                    'label' =>  'Customers',
                    'url' => 'commerceinsights/customers'
                ];
            }

            if (Craft::$app->user->checkPermission('commerceinsightsTransaction')) {
                $parent['subnav']['transactions'] = [
                    'label' =>  'Transactions',
                    'url' => 'commerceinsights/transactions'
                ];
            }
        }






        return $parent;
    }


    // Protected Methods
    // =========================================================================

    private function initRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {

                $routes       = include __DIR__ . '/routes.php';
                $event->rules = array_merge($event->rules, $routes);

            }
        );
    }



    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'commerceinsights/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
