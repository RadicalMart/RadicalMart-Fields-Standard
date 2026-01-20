<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Standard\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\RadicalMartFields\Standard\Console\UpdateOptionsCategoriesIndexesCommand;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class Standard extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  1.2.0
	 */
	protected $autoloadLanguage = true;

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
		'editor'     => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_EDITOR',
		'number'     => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_NUMBER',
		'range'      => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_RANGE'
	];

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
			'onRadicalMartNormaliseRequestData' => 'onRadicalMartNormaliseRequestData',
			'onRadicalmartPrepareFormData'      => 'onRadicalmartPrepareFormData',

			'onRadicalMartGetFieldType'    => 'onRadicalMartGetFieldType',
			'onRadicalMartGetFieldsType'   => 'onRadicalMartGetFieldsType',
			'onRadicalMartFilterFieldType' => 'onRadicalMartFilterFieldType',

			'onRadicalMartGetFieldForm'      => 'onRadicalMartGetFieldForm',
			'onRadicalMartAfterGetFieldForm' => 'onRadicalMartAfterGetFieldForm',

			'onRadicalMartGetProductFieldXml'                 => 'onRadicalMartGetProductFieldXml',
			'onRadicalMartGetMetaVariabilityProductsFieldXml' => 'onRadicalMartGetMetaVariabilityProductsFieldXml',

			'onRadicalMartGetFilterFieldXml'        => 'onRadicalMartGetFilterFieldXml',
			'onRadicalMartGetFilterItemsQueryPaths' => 'onRadicalMartGetFilterItemsQueryPaths',
			'onRadicalMartGetFilterItemsQuery'      => 'onRadicalMartGetFilterItemsQuery',
			'onRadicalMartGetFilteredMetaProduct'   => 'onRadicalMartGetFilteredMetaProduct',

			'onRadicalMartGetProductsFieldValue' => 'onRadicalMartGetProductsFieldValue',
			'onRadicalMartGetProductFieldValue'  => 'onRadicalMartGetProductFieldValue',

			'onRadicalMartGetMetaVariabilityFieldOption'     => 'onRadicalMartGetMetaVariabilityFieldOption',
			'onRadicalMartGetMetaVariabilityProductField'    => 'onRadicalMartGetMetaVariabilityProductField',
			'onRadicalMartGetMetaVariabilityProductFieldXml' => 'onRadicalMartGetMetaVariabilityProductFieldXml',

			'onRadicalMartRegisterCLICommands' => 'onRadicalMartRegisterCLICommands'
		];
	}

	/**
	 * Prepare save options data.
	 *
	 * @param   string|null  $context  The execution context.
	 * @param   object|null  $objData  Reference to the form data object.
	 * @param   Form|null    $form     The form object, if available.
	 *
	 * @since 2.1.0
	 */
	public function onRadicalMartNormaliseRequestData(?string $context, ?object $objData, ?Form $form): void
	{
		if ($context !== 'com_radicalmart.field' || empty($objData->options))
		{
			return;
		}

		$options  = [];
		$values   = [];
		$ordering = 0;
		foreach ($objData->options as &$option)
		{
			$option['text'] = trim($option['text']);
			$value          = (!empty($option['value'])) ? $option['value'] : $option['text'];
			$value          = OutputFilter::stringURLSafe($value);

			$option['option_ordering'] = $ordering;
			$ordering++;

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

	/**
	 * Prepare load options data.
	 *
	 * @param   string|null  $context  The execution context.
	 * @param   array        $data     Reference to the form data object.
	 *
	 * @since 2.1.0
	 */
	public function onRadicalmartPrepareFormData(?string $context, array &$data): void
	{
		if ($context !== 'com_radicalmart.field' || empty($data['options']))
		{
			return;
		}

		$this->sortOptions($data['options']);
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
	public function onRadicalMartGetFieldType(?string $context = null, ?object $item = null): bool|string
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
	public function onRadicalMartFilterFieldType(?string $context = null, ?string $search = null, ?QueryInterface $query = null): void
	{
		if ($context === 'com_radicalmart.fields')
		{
			$db = $this->getDatabase();
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
	public function onRadicalMartGetFieldForm(?string $context = null, ?Form $form = null, ?Registry $tmpData = null): void
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
	protected function loadFieldProductsForm(?Form $form = null, ?Registry $tmpData = null): void
	{
		// Load global
		Form::addFormPath(JPATH_PLUGINS . '/radicalmart_fields/standard/forms/products');
		$form->loadFile('global');

		$params = $tmpData->get('params', new \stdClass());
		$type   = (!empty($params->type)) ? $params->type : false;
		if (!$type || !in_array($type, array_keys($this->types)))
		{
			return;
		}

		// Load form file
		$form->loadFile($type);

		if ($type === 'list')
		{
			$multiple = (!empty($params->multiple) && (int) $params->multiple === 1);
			if ($multiple)
			{
				$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
			}
		}
		elseif ($type === 'checkboxes')
		{
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		}
		elseif ($type === 'text')
		{
			$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		}
		elseif ($type === 'textarea')
		{
			$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		}
		elseif ($type === 'editor')
		{
			$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		}
		elseif ($type === 'number')
		{
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		}
		elseif ($type === 'range')
		{
			$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
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
	public function onRadicalMartAfterGetFieldForm(?string $context = null, ?Form $form = null, ?Registry $tmpData = null): void
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
	protected function changeFieldProductsForm(?Form $form = null, ?Registry $tmpData = null): void
	{
		$params = $tmpData->get('params', new \stdClass());
		$type   = (!empty($params->type)) ? $params->type : false;
		if (empty($type) || !in_array($type, array_keys($this->types)))
		{
			return;
		}

		if ($type === 'list')
		{
			$multiple = (!empty($params->multiple) && (int) $params->multiple === 1);
			if ($multiple)
			{
				$form->setValue('display_variability', 'params', '0');
			}
		}
		elseif ($type === 'checkboxes')
		{
			$form->setValue('display_variability', 'params', '0');
		}
		elseif ($type === 'text')
		{
			$form->setValue('display_filter', 'params', '0');
			$form->setValue('display_variability', 'params', '0');
		}
		elseif ($type === 'textarea')
		{
			$form->setValue('display_filter', 'params', '0');
			$form->setValue('display_variability', 'params', '0');
		}
		elseif ($type === 'editor')
		{
			$form->setValue('display_filter', 'params', '0');
			$form->setValue('display_variability', 'params', '0');
		}
		elseif ($type === 'number')
		{

			$form->setValue('display_variability', 'params', '0');
		}
		elseif ($type === 'range')
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
	public function onRadicalMartGetProductFieldXml(?string   $context = null, ?object $field = null,
	                                                ?Registry $tmpData = null): \SimpleXMLElement|bool
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'standard')
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type) || !in_array($type, array_keys($this->types)))
		{
			return false;
		}

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);
		$required = ((int) $field->params->get('required', 0) === 1);
		if ($required)
		{
			$fieldXML->addAttribute('required', 'true');
		}

		if ($type === 'list')
		{
			$fieldXML->addAttribute('type', 'list');
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
		elseif ($type === 'checkboxes')
		{
			$fieldXML->addAttribute('type', 'checkboxes');
			$fieldXML->addAttribute('multiple', 'true');
			$fieldXML->addAttribute('class', 'form-checks-block');
		}
		elseif ($type === 'text')
		{
			$fieldXML->addAttribute('type', 'text');
			$fieldXML->addAttribute('filter', '\Joomla\CMS\Component\ComponentHelper::filterText');
		}
		elseif ($type === 'textarea')
		{
			$fieldXML->addAttribute('type', 'textarea');
			$fieldXML->addAttribute('rows', $field->params->get('rows', 3));
			$fieldXML->addAttribute('filter', '\Joomla\CMS\Component\ComponentHelper::filterText');
		}
		elseif ($type === 'editor')
		{
			$fieldXML->addAttribute('type', 'editor');
			$fieldXML->addAttribute('filter', '\Joomla\CMS\Component\ComponentHelper::filterText');
			$fieldXML->addAttribute('rows', '10');
			$fieldXML->addAttribute('syntax', 'php');
			$fieldXML->addAttribute('buttons', ((int) $field->params->get('prepare_content', 0) === 1)
				? 'true' : 'false');
			$fieldXML->addAttribute('parentclass', 'stack');
			$fieldXML->addAttribute('labelclass', 'mb-1');
		}
		elseif ($type === 'number')
		{
			$fieldXML->addAttribute('type', 'text');
			$fieldXML->addAttribute('filter',
				'\Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper::floatClean');
		}
		elseif ($type === 'range')
		{
			$fieldXML->addAttribute('type', 'subform');
			$subformXML = $fieldXML->addChild('form');
			foreach (['from', 'to'] as $subFormKey)
			{
				$subformFieldXML = $subformXML->addChild('field');
				$subformFieldXML->addAttribute('name', $subFormKey);
				$subformFieldXML->addAttribute('type', 'text');
				$subformFieldXML->addAttribute('label',
					'PLG_RADICALMART_FIELDS_STANDARD_RANGE_' . $subFormKey);
				$subformFieldXML->addAttribute('filter',
					'\Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper::floatClean');
				if ($required)
				{
					$subformFieldXML->addAttribute('required', 'true');
				}
			}
		}

		if (!empty($field->options))
		{
			$this->sortOptions($field->options);
			foreach ($field->options as $option)
			{
				$optionXml = $fieldXML->addChild('option', htmlspecialchars($option['text']));
				$optionXml->addAttribute('value', $option['value']);
			}
		}

		return $fieldXML;
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
	 * @since  2.0.0
	 */
	public function onRadicalMartGetMetaVariabilityProductsFieldXml(?string   $context = null, ?object $field = null,
	                                                                ?Registry $tmpData = null): \SimpleXMLElement|bool
	{
		if ($context !== 'com_radicalmart.meta' || $field->plugin !== 'standard'
			|| (int) $field->params->get('display_variability', 0) !== 1)
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type) || $type !== 'list' || (int) $field->params->get('multiple', 0) === 1)
		{
			return false;
		}

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('type', 'list');
		$fieldXML->addAttribute('label', $field->title);

		if (!empty($field->options))
		{
			$this->sortOptions($field->options);

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
	 * @param   string|null  $context   Context selector string.
	 * @param   object|null  $field     Field definition object.
	 * @param   mixed        $data      Form data.
	 * @param   object|bool  $category  Active category object, or null.
	 * @param   array        $currency  Current currency data.
	 *
	 * @return \SimpleXMLElement|bool SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFilterFieldXml(?string     $context = null, ?object $field = null, mixed $data = [],
	                                               object|bool $category = false, array $currency = []): \SimpleXMLElement|bool
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products'])
			|| $field->plugin !== 'standard'
			|| $field->params->get('display_filter', 0) === 0)
		{
			return false;
		}

		$type = $field->params->get('type');
		if (!in_array($type, ['list', 'checkboxes', 'number', 'range']))
		{
			return false;
		}

		$fieldXML = new \SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);

		if (in_array($type, ['list', 'checkboxes']))
		{
			$display     = $field->params->get('display_filter_as', 'list');
			$displayType = ($display === 'list') ? 'list' : 'RMFieldsStandard_Filter_' . $display;
			$fieldXML->addAttribute('type', $displayType);

			if ($displayType === 'list')
			{
				$optionXml = $fieldXML->addChild('option', 'JOPTION_DO_NOT_USE');
				$optionXml->addAttribute('value', '');
			}
			else
			{
				$fieldXML->addAttribute('addfieldprefix',
					'Joomla\Plugin\RadicalMartFields\Standard\Field');
				$fieldXML->addAttribute('multiple', 'true');
			}

			if (!empty($field->options))
			{
				$this->sortOptions($field->options);

				$optionXml = null;
				foreach ($field->options as $option)
				{
					if ($category && !empty($option['option_categories']))
					{
						$option['option_categories'] = ArrayHelper::toInteger(explode(',', $option['option_categories']));
						if (!in_array((int) $category->id, $option['option_categories']))
						{
							continue;
						}
					}

					$optionXml = $fieldXML->addChild('option', htmlspecialchars($option['text']));
					$optionXml->addAttribute('value', $option['value']);
					$optionXml->addAttribute('image', $option['image']);
				}

				if ($optionXml === null)
				{
					return false;
				}
			}
		}
		elseif (in_array($type, ['number', 'range']))
		{
			$fieldXML->addAttribute('type', 'RMFieldsStandard_Filter_Range');
			$fieldXML->addAttribute('addfieldprefix',
				'Joomla\Plugin\RadicalMartFields\Standard\Field');
		}

		return $fieldXML;
	}

	/**
	 * Method to prepare subquery json table columns.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   array   $fields   Plugin fields array.
	 * @param   array   $paths    Current path array.
	 *
	 * @since 2.0.0
	 */
	public function onRadicalMartGetFilterItemsQueryPaths(string $context, array $fields, array &$paths): void
	{
		foreach ($fields as $field)
		{
			$type = $field->params->get('type');
			if (empty($type) || !in_array($type, ['list', 'checkboxes', 'number', 'range']))
			{
				continue;
			}

			if ($type === 'list')
			{
				$multiple             = $field->params->get('multiple', false);
				$paths[$field->alias] = ($multiple) ? 'JSON' : 'VARCHAR(100)';
			}
			elseif ($type === 'checkboxes')
			{
				$paths[$field->alias] = 'JSON';
			}
			elseif ($type === 'number')
			{
				$paths[$field->alias] = 'DOUBLE';
			}
			elseif ($type === 'range')
			{
				foreach (['from', 'to'] as $subField)
				{
					$paths[$field->alias . '.' . $subField] = [
						'name' => $field->alias . '_' . $subField,
						'type' => 'DOUBLE',
						'path' => '$."' . $field->alias . '"."' . $subField . '"',
					];
				}
			}
		}
	}

	/**
	 * Method to set fields query.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   array              $fields    Plugin fields array.
	 * @param   DatabaseInterface  $db        Current database object.
	 * @param   QueryInterface     $query     Current main query object.
	 * @param   QueryInterface     $subQuery  Current subquery object.
	 *
	 * @since 2.0.0
	 */
	public function onRadicalMartGetFilterItemsQuery(string         $context, array $fields, DatabaseInterface $db,
	                                                 QueryInterface $query, QueryInterface $subQuery): void
	{
		foreach ($fields as $field)
		{
			$type = $field->params->get('type');
			if (empty($type) || !in_array($type, ['list', 'checkboxes', 'number', 'range']))
			{
				continue;
			}

			$value = (is_array($field->filter_value)) ? $field->filter_value : [(string) $field->filter_value];
			if ($type === 'list' || $type === 'checkboxes')
			{
				$multiple = ($type === 'checkboxes' || $field->params->get('multiple', false));
				$column   = $db->quoteName('fjt.' . $field->alias);

				if ($multiple)
				{
					foreach ($value as $val)
					{
						$subQuery->where('JSON_SEARCH(' . implode(', ', [
								$column,
								$db->quote('one'),
								$db->quote($val),
								'NULL',
								$db->quote('$[*]')
							]) . ') IS NOT NULL');
					}
				}
				else
				{
					$subQuery->whereIn($column, $value, ParameterType::STRING);
				}
			}
			elseif ($type === 'number' || $type === 'range')
			{
				if (empty($value['from']) && empty($value['to']))
				{
					return;
				}

				foreach ($value as &$val)
				{
					$val = NumberHelper::floatClean($val);
				}

				$bind  = ':' . str_replace(['-', '.'], '_',
						'filter_field_' . $field->alias . '_');
				$binds = [
					'from' => $bind . 'from',
					'to'   => $bind . 'to',
				];

				if ($type === 'number')
				{
					$column = $db->quoteName('fjt.' . $field->alias);
					if (!empty($value['from']))
					{
						$subQuery->where($column . ' >= ' . $binds['from'])
							->bind($binds['from'], $value['from']);
					}

					if (!empty($value['to']))
					{
						$subQuery->where($column . ' <= ' . $bind . 'to')
							->bind($binds['to'], $value['to']);
					}
				}
				if ($type === 'range')
				{
					$columns = [
						'from' => $db->quoteName('fjt.' . $field->alias . '_from'),
						'to'   => $db->quoteName('fjt.' . $field->alias . '_to'),
					];

					if (!empty($value['from']))
					{
						$subQuery->where($columns['to'] . ' >= ' . $binds['from'])
							->bind($binds['from'], $value['from']);
					}

					if (!empty($value['to']))
					{
						$subQuery->where($columns['from'] . ' <= ' . $binds['to'])
							->bind($binds['to'], $value['to']);
					}
				}
			}
		}
	}

	/**
	 * Method to filter products in category meta-product.
	 *
	 * @param   string      $context  Context selector string.
	 * @param   array       $fields   Plugin fields array.
	 * @param   array|bool  $product  Current product data.
	 *
	 * @since 2.1.0
	 */
	public function onRadicalMartGetFilteredMetaProduct(string $context, array $fields, array|bool &$product): void
	{
		if (!$product)
		{
			return;
		}
		foreach ($fields as $field)
		{
			$type = $field->params->get('type');
			if (empty($type) || !in_array($type, ['list', 'checkboxes', 'number', 'range']))
			{
				continue;
			}

			if (!isset($product['fields'][$field->alias]))
			{

				$product = false;

				return;
			}

			$fieldValue = (is_array($field->filter_value)) ? $field->filter_value : [(string) $field->filter_value];
			if ($type === 'list' || $type === 'checkboxes')
			{
				$productValue = (is_array($product['fields'][$field->alias])) ? $product['fields'][$field->alias]
					: [(string) $product['fields'][$field->alias]];
				$compare      = array_intersect($fieldValue, $productValue);
				if (count($compare) === 0)
				{
					$product = false;

					return;
				}
			}
			elseif ($type === 'number' || $type === 'range')
			{

				if (empty($fieldValue['from']) && empty($fieldValue['to']))
				{
					return;
				}

				foreach ($fieldValue as &$val)
				{
					$val = NumberHelper::floatClean($val);
				}

				if ($type === 'number')
				{
					$productValue = NumberHelper::floatClean($product['fields'][$field->alias]);

					if (isset($fieldValue['from']) && $productValue < $fieldValue['from'])
					{
						$product = false;

						return;
					}

					if (isset($fieldValue['to']) && $productValue > $fieldValue['to'])
					{
						$product = false;

						return;
					}
				}
				else
				{
					$productValue = (is_array($product['fields'][$field->alias])) ? $product['fields'][$field->alias]
						: ['from' => $product['fields'][$field->alias]];

					if (!isset($productValue['from']) && !isset($productValue['to']))
					{
						$product = false;

						return;
					}
					foreach ($productValue as &$pv)
					{
						$pv = NumberHelper::floatClean($pv);
					}

					if (isset($fieldValue['from']) && $productValue['to'] < $fieldValue['from'])
					{
						$product = false;

						return;
					}

					if (isset($fieldValue['to']) && $productValue['from'] > $fieldValue['to'])
					{
						$product = false;

						return;
					}
				}
			}
		}
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   mixed|null   $value    Field value.
	 *
	 * @return  string|false  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsFieldValue(?string $context = null, ?object $field = null, mixed $value = null): bool|string
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products']) || $field->plugin !== 'standard')
		{
			return false;
		}

		return ((int) $field->params->get('display_products', 1) === 0) ? false
			: $this->getFieldValue($context, $field, $value, $field->params->get('display_products_as', 'string'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string|null        $context  Context selector string.
	 * @param   object|null        $field    Field data object.
	 * @param   array|string|null  $value    Field value.
	 *
	 * @return bool|string Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldValue(?string           $context = null, ?object $field = null,
	                                                  array|string|null $value = null): bool|string
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'standard')
		{
			return false;
		}

		return ((int) $field->params->get('display_product', 1) === 0) ? false
			: $this->getFieldValue($context, $field, $value, $field->params->get('display_product_as', 'string'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   mixed|null   $value    Field value.
	 * @param   string       $layout   Layout name.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	protected function getFieldValue(?string $context = null, ?object $field = null, mixed $value = null, string $layout = 'string'): bool|string
	{
		if (empty($field) || empty($value))
		{
			return false;
		}

		$type = $field->params->get('type');
		if (empty($type) || !in_array($type, array_keys($this->types)))
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
		elseif ($type === 'number')
		{
			$html = NumberHelper::floatClean($value);
		}
		elseif ($type === 'range')
		{
			$numbers = [];
			if (!empty($value['from']))
			{
				$numbers[] = NumberHelper::floatClean($value['from']);
			}
			if (!empty($value['to']))
			{
				$numbers[] = NumberHelper::floatClean($value['to']);
			}

			$html = implode('-', $numbers);
		}
		else
		{
			if (!is_array($value))
			{
				$value = [(string) $value];
			}
			else
			{
				foreach ($value as &$val)
				{
					$val = (string) $val;
				}
			}

			$values = [];
			$this->sortOptions($field->options);
			foreach ($field->options as $o => $option)
			{
				if (!in_array((string) $o, $value, true))
				{
					continue;
				}

				$values[] = ($layout === 'string') ? Text::_($option['text']) : $option;
			}

			$html = ($layout === 'string') ? implode(', ', $values)
				: LayoutHelper::render('plugins.radicalmart_fields.standard.display.' . $layout,
					['field' => $field, 'values' => $values]);
		}

		// Prepare value content
		if ((int) $field->params->get('prepare_content', 0) === 1)
		{
			$html = HTMLHelper::_('content.prepare', $html, '', $context);
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
	public function onRadicalMartGetMetaVariabilityProductField(?string $context = null, ?object $field = null,
	                                                            ?object $meta = null): bool
	{
		if ($context !== 'com_radicalmart.product'
			|| $field->plugin !== 'standard'
			|| $field->params->get('type') !== 'list'
			|| (int) $field->params->get('multiple', 0) === 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   object|null  $option  Select option object.
	 * @param   object|null  $field   Field data object.
	 * @param   null         $value
	 *
	 * @return  bool  True on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityFieldOption(?object $option = null, ?object $field = null,
	                                                                   $value = null): bool
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
	public function onRadicalMartGetMetaVariabilityProductFieldXml(?string $context = null, ?object $field = null,
	                                                               ?object $meta = null, ?object $product = null): \SimpleXMLElement|bool
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
		$fieldXML->addAttribute('type', 'RMFieldsStandard_Variability');
		$fieldXML->addAttribute('addfieldprefix', 'Joomla\Plugin\RadicalMartFields\Standard\Field');
		$fieldXML->addAttribute('sublayout', $field->params->get('display_variability_as', 'list'));
		$hasOptions = false;

		if (!empty($field->options))
		{
			$this->sortOptions($field->options);

			foreach ($field->options as $option)
			{
				$disabled = (!in_array($option['value'], $fieldValues, true));
				if (!$disabled)
				{
					$hasOptions = true;
				}

				$optionXml = $fieldXML->addChild('option', htmlspecialchars($option['text']));
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

	/**
	 * Add fields cli command.
	 *
	 * @param   array     $commands  Updated commands array.
	 * @param   Registry  $params    RadicalMart params.
	 *
	 * @since 2.1.0
	 */
	public function onRadicalMartRegisterCLICommands(array &$commands, Registry $params): void
	{
		$commands[] = UpdateOptionsCategoriesIndexesCommand::class;
	}

	/**
	 * Method to sort field options.
	 *
	 * @param   array  $options  Options data array.
	 *
	 * @since 2.1.0
	 */
	protected function sortOptions(array &$options): void
	{
		$ordering = 0;
		foreach ($options as &$option)
		{
			if (isset($option['option_ordering']))
			{
				break;
			}

			$option['option_ordering'] = $ordering;

			$ordering++;
		}

		uasort($options, function (array $a, array $b) {
			return (int) $a['option_ordering'] <=> (int) $b['option_ordering'];
		});
	}
}