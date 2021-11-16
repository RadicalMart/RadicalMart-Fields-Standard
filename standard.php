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
				$form->setFieldAttribute('display_filter', 'type', 'hidden', 'params');
				$form->setValue('display_filter', 'params', 0);
			}
			else
			{
				$form->removeField('display_product', 'params');
				$form->removeField('display_products', 'params');
				$form->removeField('display_filter', 'params');
			}
			$form->loadFile($type);
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
					$sql[] = 'JSON_CONTAINS(p.fields, ' . $db->quote($val) . ', ' . $db->quote('$.' . $field->alias) . ')';
				}
				else
				{
					$sql[] = 'JSON_VALUE(p.fields, ' . $db->quote('$.' . $field->alias) . ') = ' . $db->quote($val);
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
}
