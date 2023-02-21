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

namespace Joomla\Plugin\RadicalMartFields\Standard\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\RadioField;
use Joomla\CMS\Language\Text;

class VariabilityField extends RadioField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.1.0
	 */
	protected $type = 'variability';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  1.1.0
	 */
	protected $layout = 'plugins.radicalmart_fields.standard.field.variability';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.1.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$subLayout    = (!empty($this->element['sublayout'])) ? (string) $this->element['sublayout'] : 'list';
			$this->layout .= '.' . $subLayout;
		}

		return $return;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.1.0
	 */
	protected function getOptions()
	{
		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$options   = [];

		foreach ($this->element->xpath('option') as $option)
		{
			$value = (string) $option['value'];
			$text  = trim((string) $option) != '' ? trim((string) $option) : $value;

			$checked = (string) $option['checked'];
			$checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

			$selected = (string) $option['selected'];
			$selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

			$disabled = (string) $option['disabled'];
			$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
			$disabled = $disabled || ($this->readonly && $value != $this->value);

			$tmp = [
				'value'    => $value,
				'image'    => (string) $option['image'],
				'text'     => Text::alt($text, $fieldname),
				'selected' => ($checked || $selected),
				'checked'  => ($checked || $selected),
				'disable'  => $disabled
			];


			// Add the option object to the result set.
			$options[] = (object) $tmp;
		}

		return $options;
	}
}