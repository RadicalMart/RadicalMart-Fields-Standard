<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_fields_standard
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

class plgRadicalMart_FieldsStandard extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  1.0.0
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  JDatabaseDriver
	 *
	 * @since  1.0.0
	 */
	protected $db = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Field type.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $_types = [
		'list'       => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_LIST',
		'checkboxes' => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_CHECKBOXES',
		'text'       => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_TEXT',
		'textarea'   => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_TEXTAREA',
		'editor'     => 'PLG_RADICALMART_FIELDS_STANDARD_TYPE_EDITOR'
	];

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $item     List item object.
	 *
	 * @return string|false Field type constant on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetFieldType($context = null, $item = null)
	{
		$type = $item->params->get('type');

		return (isset($this->_types[$type])) ? $this->_types[$type] : false;
	}

	/**
	 * Method to add field type to admin types field.
	 *
	 * @return array Field types associative array [type => text].
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetFieldsType()
	{
		return $this->_types;
	}


	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string                           $context  Context selector string.
	 * @param   string                           $search   List item object.
	 * @param   \Joomla\Database\QueryInterface  $query    List item object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartFilterFieldType($context = null, $search = null, $query = null)
	{
		if ($context === 'com_radicalmart.fields')
		{
			$db = $this->db;
			$query->where('JSON_VALUE(f.params, ' . $db->quote('$."type"') . ') = ' . $db->quote($search));
		}
	}


	/**
	 * Method to add field config.
	 *
	 * @param   string    $context  Context selector string.
	 * @param   Form      $form     Form object.
	 * @param   Registry  $tmpData  Temporary form data.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFieldForm($context = null, $form = null, $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field') return;
		if ($tmpData->get('plugin') !== 'standard') return;

		Form::addFormPath(__DIR__ . '/config');
		$form->loadFile('global');

		$type = (!empty($tmpData->get('params', new stdClass())->type))
			? $tmpData->get('params', new stdClass())->type : false;

		if ($type)
		{
			if ($type === 'text' || $type === 'textarea' || $type === 'editor')
			{
				$form->removeField('display_filter', 'params');
			}
			else
			{
				$form->removeField('display_product', 'params');
				$form->removeField('display_products', 'params');
				$form->removeField('display_filter', 'params');
				$form->removeField('display_variability', 'params');
			}
			$form->loadFile($type);

			$multiple = (!empty($tmpData->get('params', new stdClass())->multiple))
				? $tmpData->get('params', new stdClass())->multiple : false;
			if ($type !== 'list' || $multiple)
			{

				$form->removeField('display_variability', 'params');
				$form->removeField('display_variability_as', 'params');
			}
		}
	}

	/**
	 * Prepare options data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $objData  Input data.
	 * @param   Form    $form     Joomla Form object.
	 *
	 * @throws  Exception
	 *
	 * @since  1.0.0
	 */
	public function onContentNormaliseRequestData($context, $objData, $form)
	{
		if ($context === 'com_radicalmart.field')
		{
			if ($objData->plugin === 'standard' && !empty($objData->options))
			{
				$options = array();
				$values  = array();
				foreach ($objData->options as &$option)
				{
					$option['text'] = trim($option['text']);
					$value          = (!empty($option['value'])) ? $option['value'] : $option['text'];
					$value          = OutputFilter::stringURLSafe($value);

					while (in_array($value, $values)) $value = StringHelper::increment($value, 'dash');
					$values[] = $value;

					$option['value'] = $value;
					$options[$value] = $option;
				}

				$objData->options = $options;
			}
		}
	}

	/**
	 * Method to add field to product form.
	 *
	 * @param   string    $context  Context selector string.
	 * @param   object    $field    Field data object.
	 * @param   Registry  $tmpData  Temporary form data.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldXml($context = null, $field = null, $tmpData = null)
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'standard') return false;
		if (!$type = $field->params->get('type')) return false;

		$fieldXML = new SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('type', $type);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);

		if ((int) $field->params->get('required', 0))
		{
			$fieldXML->addAttribute('required', 'true');
		}

		if ($type === 'checkboxes' || ($type === 'list' && (int) $field->params->get('multiple', 0)))
		{
			$fieldXML->addAttribute('multiple', 'true');
		}

		if ($type === 'textarea')
		{
			$fieldXML->addAttribute('class', 'span12');
			$fieldXML->addAttribute('rows', $field->params->get('rows', 3));
		}

		if ($type === 'editor')
		{
			$fieldXML->addAttribute('buttons', 'true');
			$fieldXML->addAttribute('filter', 'JComponentHelper::filterText');
		}

		if ($type === 'list' && !(int) $field->params->get('multiple', 0) && (int) $field->params->get('null_value', 1))
		{
			$optionXml = $fieldXML->addChild('option', 'JOPTION_DO_NOT_USE');
			$optionXml->addAttribute('value', '');
		}

		if (!empty($field->options))
		{
			foreach ($field->options as $option)
			{
				$optionXml = $fieldXML->addChild('option', $option['text']);
				$optionXml->addAttribute('value', $option['value']);
			}
		}

		return $fieldXML;
	}

	/**
	 * Method to add field to filter form.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $field    Field data object.
	 * @param   array   $data     Data.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFilterFieldXml($context = null, $field = null, $data = null)
	{
		if ($context !== 'com_radicalmart.category' && $context !== 'com_radicalmart.products') return false;
		if ($field->plugin !== 'standard') return false;
		if (!(int) $field->params->get('display_filter', 1)) return false;

		$type = $field->params->get('type');
		if (!$type || $type === 'text' || $type === 'textarea' || $type === 'editor') return false;

		$display = $field->params->get('display_filter_as', 'list');
		if ($display === 'list') $displayType = 'list';
		else $displayType = 'filter_' . $display;

		$fieldXML = new SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);
		$fieldXML->addAttribute('type', $displayType);
		if ($displayType !== 'list')
		{
			$fieldXML->addAttribute('addfieldpath', '/plugins/radicalmart_fields/standard/fields');
		}

		if ($displayType === 'list')
		{
			$optionXml = $fieldXML->addChild('option', 'JOPTION_DO_NOT_USE');
			$optionXml->addAttribute('value', '');
		}
		else
		{
			$fieldXML->addAttribute('multiple', 'true');
		}

		if (!empty($field->options))
		{
			foreach ($field->options as $option)
			{
				$optionXml = $fieldXML->addChild('option', $option['text']);
				$optionXml->addAttribute('value', $option['value']);
				$optionXml->addAttribute('image', $option['image']);
			}
		}

		return $fieldXML;
	}

	/**
	 * Method to modify query.
	 *
	 * @param   string          $context  Context selector string.
	 * @param   JDatabaseQuery  $query    JDatabaseQuery  A JDatabaseQuery object to retrieve the data set
	 * @param   object          $field    Field data object.
	 * @param   array|string    $value    Value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsListQuery($context = null, $query = null, $field = null, $value = null)
	{
		if ($context !== 'com_radicalmart.category' && $context !== 'com_radicalmart.products') return;
		if ($field->plugin !== 'standard') return;

		$type = $field->params->get('type');
		if (!$type || $type === 'text' || $type === 'textarea' || $type === 'editor') return;

		if (!is_array($value)) $value = array($value);

		$multiple = $field->params->get('multiple', false);
		if ($type === 'checkboxes') $multiple = 'true';

		$db  = $this->db;
		$sql = array();
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
	 * @param   string        $context  Context selector string.
	 * @param   object        $field    Field data object.
	 * @param   array|string  $value    Field value.
	 *
	 * @return  string  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsFieldValue($context = null, $field = null, $value = null)
	{
		if ($context !== 'com_radicalmart.category' && $context !== 'com_radicalmart.products') return false;
		if ($field->plugin !== 'standard') return false;

		if (!(int) $field->params->get('display_products', 1)) return false;

		return $this->getFieldValue($field, $value, $field->params->get('display_products_as', 'string'));
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
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'standard') return false;
		if (!(int) $field->params->get('display_product', 1)) return false;

		return $this->getFieldValue($field, $value, $field->params->get('display_product_as', 'string'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   object        $field   Field data object.
	 * @param   string|array  $value   Field value.
	 * @param   string        $layout  Layout name.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	protected function getFieldValue($field = null, $value = null, $layout = 'string')
	{
		if (empty($field)) return false;
		if (empty($value)) return false;
		if (!$type = $field->params->get('type')) return false;

		if ($type === 'text' || $type === 'editor') $html = $value;
		elseif ($type === 'textarea') $html = nl2br($value);
		else
		{
			if (!is_array($value)) $value = array($value);

			$values = array();
			foreach ($field->options as $o => $option)
			{
				if (!in_array($o, $value)) continue;
				$values[] = ($layout === 'string') ? Text::_($option['text']) : $option;
			}

			$html = ($layout === 'string') ? implode(', ', $values)
				: LayoutHelper::render('plugins.radicalmart_fields.standard.display.' . $layout, array(
					'field' => $field, 'values' => $values));
		}

		return $html;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   object  $option  Select option object.
	 * @param   object  $field   Field data object.
	 *
	 * @return  bool  True on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityFieldOption($option = null, $field = null, $value = null)
	{
		if ($field->plugin !== 'standard' ||
			$field->params->get('type') !== 'list' || (int) $field->params->get('multiple', 0)) return false;

		return (int) $field->params->get('display_variability', 1);
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $field    Field data object.
	 * @param   object  $meta     Meta product data object.
	 *
	 * @return  bool  True on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityProductField($context = null, $field = null, $meta = null)
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'standard' ||
			$field->params->get('type') !== 'list' || (int) $field->params->get('multiple', 0)) return false;

		return true;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $field    Field data object.
	 * @param   object  $meta     Meta product data object.
	 * @param   object  $product  Current product data object.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityProductFieldXml($context = null, $field = null, $meta = null, $product = null)
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'standard' ||
			$field->params->get('type') !== 'list' || (int) $field->params->get('multiple', 0)) return false;

		if (!(int) $field->params->get('display_variability', 1)) return false;

		$fieldValues = (isset($meta->fieldValues[$field->alias])) ? $meta->fieldValues[$field->alias] : false;
		if (!$fieldValues) return false;

		$fieldXML = new SimpleXMLElement('<field/>');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('description', $field->description);
		$fieldXML->addAttribute('type', 'variability');
		$fieldXML->addAttribute('addfieldpath', '/plugins/radicalmart_fields/standard/fields/');
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
				if ($disabled) $optionXml->addAttribute('disabled', true);
			}
		}

		return ($hasOptions) ? $fieldXML : false;
	}
}
