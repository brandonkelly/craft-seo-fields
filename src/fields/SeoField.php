<?php
/**
 * SEO Fields plugin for Craft CMS 3.x
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields\fields;

use Craft;
use craft\base\ElementInterface;

use craft\base\Field;
use craft\helpers\Json;
use studioespresso\seofields\models\SeoFieldModel;
use studioespresso\seofields\SeoFields;
use yii\db\Schema;

/**
 * SeoField Field
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SeoField extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */

    public $tabs = [];
    public $allowSitenameOverwrite = false;
    public $allowSitenameDisable = false;

    // Static Methods
    // =========================================================================

    /**
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('seo-fields', 'SEO Fields');
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules = array_merge($rules, [
            ['allowSitenameOverwrite', 'boolean'],
            ['allowSitenameDisable', 'boolean'],

        ]);
        return $rules;
    }

    public static function isMultiInstance(): bool
    {
        return false;
    }


    /**
     * Returns the column type that this field should get within the content table.
     *
     * This method will only be called if [[hasContentColumn()]] returns true.
     *
     * @return string The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     * to convert the give column type to the physical one. For example, `string` will be converted
     * as `varchar(255)` and `string(100)` becomes `varchar(100)`. `not null` will automatically be
     * appended as well.
     * @see \yii\db\QueryBuilder::getColumnType()
     */
    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    /**
     * Normalizes the field’s value for use.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return mixed The prepared field value
     */
    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        $model = new SeoFieldModel();
        if (is_array($value)) {
            $model->setAttributes($value);
        } elseif ($value instanceof SeoFieldModel) {
            $model = $value;
        } elseif ($value) {
            $model->setAttributes(Json::decodeIfJson($value));
        }
        return $model;
    }

    /**
     * Modifies an element query.
     *
     * This method will be called whenever elements are being searched for that may have this field assigned to them.
     *
     * @return null|false `false` in the event that the method is sure that no elements are going to be found.
     */
    public function serializeValue($value, ElementInterface $element = null): mixed
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * Returns the component’s settings HTML.
     *
     * @return string|null
     */
    public function getSettingsHtml(): ? string
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'seo-fields/_components/fields/SeoField_settings',
            [
                'field' => $this,
            ]
        );
    }

    /**
     *
     * @param mixed $value The field’s value. This will either be the [[normalizeValue() normalized value]],
     *                                               raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return string The input HTML.
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $value->siteId = $element->siteId;

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
        ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').SeoField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'seo-fields/_components/fields/SeoField_input',
            [
                'element' => $element,
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }
}
