<?php
/**
 * SEO Fields plugin for Craft CMS 3.x
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields;

use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fields;
use craft\web\UrlManager;
use studioespresso\seofields\fields\SeoField;
use studioespresso\seofields\models\Settings;
use studioespresso\seofields\services\SeoFieldsService as SeoFieldsServiceService;
use studioespresso\seofields\variables\SeoFieldsVariable;
use yii\base\Event;

/**
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 *
 * @property  SeoFieldsServiceService $seoFieldsService
 * @method    Settings getSettings()
 */
class SeoFields extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SeoFields::$plugin
     *
     * @var SeoFields
     */
    public static $plugin;

    // Public Properties
    // =========================================================================
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SeoField::class;
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Register our Control Panel routes
                $event->rules = array_merge($event->rules, [
                    'seo-fields' => 'seo-fields/default/index',
                    'seo-fields/defaults' => 'seo-fields/default/index',
                    'seo-fields/defaults/<siteHandle:{handle}>' => 'seo-fields/default/defaults',
                    'seo-fields/robots' => 'seo-fields/robots/index',
                    'seo-fields/robots/<siteHandle:{handle}>' => 'seo-fields/robots/robots',
                ]);
            }
        );
    }

    public function getCpNavItem()
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        // Only show sub-navs the user has permission to view
        $subNavs['defaults'] = [
            'label' => 'Defaults',
            'url' => 'seo-fields/defaults',
        ];
        $subNavs['robots'] = [
            'label' => 'Robots.txt',
            'url' => 'seo-fields/robots',
        ];
        $navItem = array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
        return $navItem;
    }

    // Protected Methods
    // =========================================================================
    // Protected Methods
    // =========================================================================
    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'seo-fields/_settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }


}
