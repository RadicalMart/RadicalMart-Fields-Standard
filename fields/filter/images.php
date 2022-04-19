<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_fields_standard
 * @version     1.1.0
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('checkboxes');

class JFormFieldFilter_Images extends JFormFieldCheckboxes
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'filter_images';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $layout = 'plugins.radicalmart_fields.standard.field.filter.images';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$options   = array();

		foreach ($this->element->xpath('option') as $option)
		{
			$value = (string) $option['value'];
			$text  = trim((string) $option) != '' ? trim((string) $option) : $value;

			$checked = (string) $option['checked'];
			$checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

			$selected = (string) $option['selected'];
			$selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

			$tmp = array(
				'value'    => $value,
				'image'    => (string) $option['image'],
				'text'     => Text::alt($text, $fieldname),
				'selected' => ($checked || $selected),
				'checked'  => ($checked || $selected),
			);


			// Add the option object to the result set.
			$options[] = (object) $tmp;
		}

		reset($options);

		return $options;
	}
}