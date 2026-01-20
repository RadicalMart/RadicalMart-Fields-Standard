<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     2.1.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Standard\Field\RMFieldsStandard\Filter;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;

class RangeField extends TextField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  2.1.0
	 */
	protected $type = 'RMFieldsStandard_Filter_Range';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  2.1.0
	 */
	protected $layout = 'plugins.radicalmart_fields.standard.field.filter.range';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value.
	 *
	 * @throws  \Exception
	 *
	 * @return  bool  True on success.
	 *
	 * @since  2.1.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		if (!parent::setup($element, $value, $group))
		{
			return false;
		}

		$this->multiple = true;

		return true;
	}
}