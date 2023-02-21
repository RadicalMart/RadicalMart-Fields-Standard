<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     1.2.2
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Standard\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\QueryInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

class Standard extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  1.2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  1.2.0
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  \Joomla\Database\DatabaseDriver
	 *
	 * @since  1.2.0
	 */
	protected $db = null;

	/**
	 * Field types.
	 *
	 * @var  array
	 *
	 * @since  1.2.0
	 */
	protected array $types = [
		'list'       => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_LIST',
		'checkboxes' => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_CHECKBOXES',
		'text'       => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_TEXT',
		'textarea'   => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_TEXTAREA',
		'editor'     => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_EDITOR'
	];

	/**
	 * Field types each not display on filter.
	 *
	 * @var  array
	 *
	 * @since  1.2.0
	 */
	protected array $noFilterTypes = ['editor', 'text', 'textarea'];

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentNormaliseRequestData'                  => 'onContentNormaliseRequestData',
			'onRadicalMartGetFieldType'                      => 'onRadicalMartGetFieldType',
			'onRadicalMartGetFieldsType'                     => 'onRadicalMartGetFieldsType',
			'onRadicalMartFilterFieldType'                   => 'onRadicalMartFilterFieldType',
			'onRadicalMartGetFieldForm'                      => 'onRadicalMartGetFieldForm',
			'onRadicalMartAfterGetFieldForm'                 => 'onRadicalMartAfterGetFieldForm',
			'onRadicalMartGetProductFieldXml'                => 'onRadicalMartGetProductFieldXml',
			'onRadicalMartGetFilterFieldXml'                 => 'onRadicalMartGetFilterFieldXml',
			'onRadicalMartGetProductsListQuery'              => 'onRadicalMartGetProductsListQuery',
			'onRadicalMartGetProductsFieldValue'             => 'onRadicalMartGetProductsFieldValue',
			'onRadicalMartGetProductFieldValue'              => 'onRadicalMartGetProductFieldValue',
			'onRadicalMartGetMetaVariabilityFieldOption'     => 'onRadicalMartGetMetaVariabilityFieldOption',
			'onRadicalMartGetMetaVariabilityProductField'    => 'onRadicalMartGetMetaVariabilityProductField',
			'onRadicalMartGetMetaVariabilityProductFieldXml' => 'onRadicalMartGetMetaVariabilityProductFieldXml'
		];
	}

	/**
	 * Prepare options data.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  1.0.0
	 */
	public function onContentNormaliseRequestData(Event $event)
	{
		$context = $event->getArgument('0');
		$objData = $event->getArgument('1');
		if ($context === 'com_radicalmart.field')
		{
			if ($objData->plugin === 'standard' && !empty($objData->options))
			{
				$options = [];
				$values  = [];
				foreach ($objData->options as &$option)
				{
					$option['text'] = trim($option['text']);
					$value          = (!empty($option['value'])) ? $option['value'] : $option['text'];
					$value          = OutputFilter::stringURLSafe($value);

					while (in_array($value, $values))
					{
						$value = StringHelper::increment($value, 'dash');
					}
					$values[] = $value;

					$option['value'] = $value;
					$options[$value] = $option;
				}

				$objData->options = $options;
			}
		}

		$event->setArgument('1', $objData);
	}

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $item     List item object.
	 *
	 * @return string|false Field type constant on success, False on failure.
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartGetFieldType(string $context = null, object $item = null)
	{
		$type = $item->params->get('type');

		return (isset($this->types[$type])) ? $this->types[$type] : false;
	}

	/**
	 * Method to add field type to admin types field.
	 *
	 * @return array Field types associative array [type => text].
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartGetFieldsType(): array
	{
		return $this->types;
	}

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string|null          $context  Context selector string.
	 * @param   string|null          $search   List item object.
	 * @param   QueryInterface|null  $query    A QueryInterface object to retrieve the data set.
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartFilterFieldType(string $context = null, string $search = null, QueryInterface $query = null)
	{
		if ($context === 'com_radicalmart.fields')
		{
			$db = $this->db;
			$query->where('JSON_VALUE(f.params, ' . $db->quote('$."type"') . ') = ' . $db->quote($search));
		}
	}

	/**
	 * Method to field type form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFieldForm(string $context = null, Form $form = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'standard')
		{
			return;
		}

		$area    = $tmpData->get('area');
		$methods = [
			'products' => 'loadFieldProductsForm'
		];

		if (isset($methods[$area]))
		{
			$method = $methods[$area];
			if (method_exists($this, $method))
			{
				$this->$method($form, $tmpData);
			}
		}
	}

	/**
	 * Method to load products field type form.
	 *
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  1.2.0
	 */
	protected function loadFieldProductsForm(Form $form = null, Registry $tmpData = null)
	{
		// Load global
		Form::addFormPath(JPATH_PLUGINS . '/radicalmart_fields/standard/forms/products');
		$form->loadFile('global');

		$params = $tmpData->get('params', new \stdClass());
		$type   = (!empty($params->type)) ? $params->type : false;

		if (in_array($type, $this->noFilterTypes))
		{
			// Set readonly
			$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
		}
		else
		{
			// Prepare ordering
			$form->removeField('display_product', 'params');
			$form->removeField('display_products', 'params');
			$form->removeField('display_filter', 'params');
			$form->removeField('display_variability', 'params');
		}

		// Load form file
		$form->loadFile($type);

		// Set variability readonly
		$multiple = (!empty($params->multiple) && (int) $params->multiple === 1);
		if ($type !== 'list' || $multiple)
		{
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
			$form->removeField('display_variability_as', 'params');
		}
	}

	/**
	 * Method to change field form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartAfterGetFieldForm(string $context = null, Form $form = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'standard')
		{
			return;
		}

		$area    = $tmpData->get('area');
		$methods = [
			'products' => 'changeFieldProductsForm'
		];

		if (isset($methods[$area]))
		{
			$method = $methods[$area];
			if (method_exists($this, $method))
			{
				$this->$method($form, $tmpData);
			}
		}
	}

	/**
	 * Method to chage  products field type form.
	 *
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  1.2.0
	 */
	protected function changeFieldProductsForm(Form &$form = null, Registry $tmpData = null)
	{
		$params = $tmpData->get('params', new \stdClass());
		$type   = (!empty($params->type)) ? $params->type : false;


		if (in_array($type, $this->noFilterTypes))
		{
			$form->setValue('display_filter', 'params', '0');
		}

		$multiple = (!empty($params->multiple) && (int) $params->multiple === 1);
		if ($type !== 'list' || $multiple)
		{
			$form->setValue('display_variability', 'params', '0');
		}
	}

	/**
	 * Method to add field to product form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   object|null    $field    Field data object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @return false|\SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldXml(string $context = null, object $field = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'standard')
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type) || !in_array($type, ['checkboxes', 'editor', 'list', 'text', 'textarea']))
		{
			return false;
		}

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('type', $type);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);

		if ((int) $field->params->get('required', 0))
		{
			$fieldXML->addAttribute('required', 'true');
		}

		if ($type === 'checkboxes')
		{
			$fieldXML->addAttribute('multiple', 'true');
		}

		if ($type === 'editor')
		{
			$fieldXML->addAttribute('filter', '\Joomla\CMS\Component\ComponentHelper::filterText');
			$fieldXML->addAttribute('height', '200');
			$fieldXML->addAttribute('rows', '10');
			$fieldXML->addAttribute('syntax', 'php');
			$fieldXML->addAttribute('buttons', 'false');
			$fieldXML->addAttribute('parentclass', 'stack');
			$fieldXML->addAttribute('labelclass', 'mb-1');
		}

		if ($type === 'list')
		{
			if ((int) $field->params->get('multiple', 0) == 1)
			{
				$fieldXML->addAttribute('multiple', 'true');
				$fieldXML->addAttribute('hint', ' ');
				$fieldXML->addAttribute('layout', 'joomla.form.field.list-fancy-select');
				$fieldXML->addAttribute('parentclass', 'stack');
				$fieldXML->addAttribute('labelclass', 'mb-1');
			}
			elseif ((int) $field->params->get('null_value', 0) === 1)
			{
				$optionXml = $fieldXML->addChild('option', 'JOPTION_DO_NOT_USE');
				$optionXml->addAttribute('value', '');
			}
		}

		if ($type === 'textarea')
		{
			$fieldXML->addAttribute('rows', $field->params->get('rows', 3));
			$fieldXML->addAttribute('filter', '\Joomla\CMS\Component\ComponentHelper::filterText');
		}

		if (!empty($field->options))
		{
			foreach ($field->options as $option)
			{
				$optionXml = $fieldXML->addChild('option', htmlspecialchars($option['text']));
				$optionXml->addAttribute('value', $option['value']);
			}
		}

		return $fieldXML;
	}

	/**
	 * Method to add field to filter form.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 *
	 * @return false|\SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFilterFieldXml(string $context = null, object $field = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products'])
			|| $field->plugin !== 'standard'
			|| $field->params->get('display_filter', 0) === 0)
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type) || in_array($type, $this->noFilterTypes))
		{
			return false;

		}

		$display     = $field->params->get('display_filter_as', 'list');
		$displayType = ($display === 'list') ? 'list' : 'filter_' . $display;

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);
		$fieldXML->addAttribute('type', $displayType);

		if ($displayType === 'list')
		{
			$optionXml = $fieldXML->addChild('option', 'JOPTION_DO_NOT_USE');
			$optionXml->addAttribute('value', '');
		}
		else
		{
			$fieldXML->addAttribute('addfieldprefix', 'Joomla\Plugin\RadicalMartFields\Standard\Field');
			$fieldXML->addAttribute('multiple', 'true');
		}

		if (!empty($field->options))
		{
			foreach ($field->options as $option)
			{
				$optionXml = $fieldXML->addChild('option', htmlspecialchars($option['text']));
				$optionXml->addAttribute('value', $option['value']);
				$optionXml->addAttribute('image', $option['image']);
			}
		}

		return $fieldXML;
	}

	/**
	 * Method to modify query.
	 *
	 * @param   string|null          $context  Context selector string.
	 * @param   QueryInterface|null  $query    A QueryInterface object to retrieve the data set.
	 * @param   object|null          $field    Field data object.
	 * @param   mixed                $value    Value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsListQuery(string $context = null, QueryInterface $query = null,
	                                                  object $field = null, $value = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products']) || $field->plugin !== 'standard')
		{
			return;
		}


		$type = $field->params->get('type');
		if (empty($type) || in_array($type, $this->noFilterTypes))
		{
			return;
		}

		if (!is_array($value))
		{
			$value = [$value];
		}

		$multiple = $field->params->get('multiple', false);
		if ($type === 'checkboxes')
		{
			$multiple = true;
		}

		$db  = $this->db;
		$sql = [];
		foreach ($value as $val)
		{
			if ($val = trim($val))
			{
				if ($multiple)
				{
					$val   = '"' . $val . '"';
					$sql[] = 'JSON_CONTAINS(p.fields, ' . $db->quote($val) . ', ' . $db->quote('$."' . $field->alias . '"') . ')';
				}
				else
				{
					$sql[] = 'JSON_VALUE(p.fields, ' . $db->quote('$."' . $field->alias . '"') . ') = ' . $db->quote($val);
				}
			}
		}

		if (!empty($sql))
		{
			$query->where('(' . implode(' OR ', $sql) . ')');
		}
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   mixed        $value    Field value.
	 *
	 * @return  string|false  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsFieldValue(string $context = null, object $field = null, $value = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products']) || $field->plugin !== 'standard')
		{
			return false;
		}

		return ((int) $field->params->get('display_products', 1) === 0) ? false
			: $this->getFieldValue($field, $value, $field->params->get('display_products_as', 'string'));

	}


	/**
	 * Method to add field value to products list.
	 *
	 * @param   string        $context  Context selector string.
	 * @param   object        $field    Field data object.
	 * @param   array|string  $value    Field value.
	 *
	 * @return  string  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldValue($context = null, $field = null, $value = null)
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'standard')
		{
			return false;
		}

		return ((int) $field->params->get('display_product', 1) === 0) ? false
			: $this->getFieldValue($field, $value, $field->params->get('display_product_as', 'string'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   object|null  $field   Field data object.
	 * @param   mixed        $value   Field value.
	 * @param   string       $layout  Layout name.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	protected function getFieldValue(object $field = null, $value = null, string $layout = 'string')
	{
		if (empty($field) || empty($value))
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type))
		{
			return false;
		}

		if ($type === 'text' || $type === 'editor')
		{
			$html = $value;
		}
		elseif ($type === 'textarea')
		{
			$html = nl2br($value);
		}
		else
		{
			if (!is_array($value))
			{
				$value = [$value];
			}

			$values = [];
			foreach ($field->options as $o => $option)
			{
				if (!in_array($o, $value))
				{
					continue;
				}

				$values[] = ($layout === 'string') ? Text::_($option['text']) : $option;
			}

			$html = ($layout === 'string') ? implode(', ', $values)
				: LayoutHelper::render('plugins.radicalmart_fields.standard.display.' . $layout,
					['field' => $field, 'values' => $values]);
		}

		return $html;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   object|null  $meta     Meta product data object.
	 *
	 * @return  bool  True on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityProductField(string $context = null, object $field = null,
	                                                            object $meta = null): bool
	{
		if ($context !== 'com_radicalmart.product'
			|| $field->plugin !== 'standard'
			|| $field->params->get('type') !== 'list'
			|| (int) $field->params->get('multiple', 0) === 1) return false;

		return true;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   object|null  $option  Select option object.
	 * @param   object|null  $field   Field data object.
	 *
	 * @return  bool  True on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityFieldOption(object $option = null, object $field = null, $value = null): bool
	{
		if ($field->plugin !== 'standard'
			|| $field->params->get('type') !== 'list'
			|| (int) $field->params->get('multiple', 0) === 1)
		{
			return false;
		}

		return ((int) $field->params->get('display_variability', 1) === 1);
	}


	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   object|null  $meta     Meta product data object.
	 * @param   object|null  $product  Current product data object.
	 *
	 * @return false|\SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityProductFieldXml(string $context = null, object $field = null,
	                                                               object $meta = null, object $product = null)
	{
		if ($context !== 'com_radicalmart.product'
			|| $field->plugin !== 'standard'
			|| $field->params->get('type') !== 'list'
			|| (int) $field->params->get('multiple', 0) == 1
			|| (int) $field->params->get('display_variability', 1) == 0)
		{
			return false;
		}


		$fieldValues = (isset($meta->fieldValues[$field->alias])) ? $meta->fieldValues[$field->alias] : false;
		if (!$fieldValues)
		{
			return false;
		}

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);
		$fieldXML->addAttribute('type', 'variability');
		$fieldXML->addAttribute('addfieldprefix', 'Joomla\Plugin\RadicalMartFields\Standard\Field');
		$fieldXML->addAttribute('sublayout', $field->params->get('display_variability_as', 'list'));
		$hasOptions = false;

		if (!empty($field->options))
		{
			foreach ($field->options as $option)
			{
				$disabled = (!in_array($option['value'], $fieldValues));
				if (!$disabled) $hasOptions = true;

				$optionXml = $fieldXML->addChild('option', $option['text']);
				$optionXml->addAttribute('value', $option['value']);
				$optionXml->addAttribute('image', $option['image']);
				if ($disabled)
				{
					$optionXml->addAttribute('disabled', true);
				}
			}
		}

		return ($hasOptions) ? $fieldXML : false;
	}
}