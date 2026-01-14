<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Standard\Field\RMFieldsStandard\Filter;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\CheckboxesField as BaseField;
use Joomla\CMS\Language\Text;

class ImagesField extends BaseField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'RMFieldsStandard_Filter_Images';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_fields.standard.field.filter.images';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions(): array
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

			$tmp = [
				'value'    => $value,
				'image'    => (string) $option['image'],
				'text'     => Text::alt($text, $fieldname),
				'selected' => ($checked || $selected),
				'checked'  => ($checked || $selected),
			];


			// Add the option object to the result set.
			$options[] = (object) $tmp;
		}

		return $options;
	}
}