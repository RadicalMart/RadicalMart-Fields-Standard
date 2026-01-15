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

namespace Joomla\Plugin\RadicalMartFields\Standard\Field\RMFieldsStandard\Filter;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\CheckboxesField as BaseField;

class CheckboxesField extends BaseField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'RMFieldsStandard_Filter_Checkboxes';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_fields.standard.field.filter.checkboxes';
}