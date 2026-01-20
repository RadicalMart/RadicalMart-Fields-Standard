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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $tmpl            The Empty form for template.
 * @var   array  $forms           Array of JForm instances for render the rows.
 * @var   bool   $multiple        The multiple state for the form field.
 * @var   int    $min             Count of minimum repeating in multiple mode.
 * @var   int    $max             Count of maximum repeating in multiple mode.
 * @var   string $name            Name of the input field.
 * @var   string $fieldname       The field name.
 * @var   string $fieldId         The field ID.
 * @var   string $control         The forms control.
 * @var   string $label           The field label.
 * @var   string $description     The field description.
 * @var   string $class           Classes for the container.
 * @var   array  $buttons         Array of the buttons that will be rendered.
 * @var   bool   $groupByFieldset Whether group the subform fields by it`s fieldset.
 */

// Load assets
/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = Factory::getApplication()->getDocument()->getWebAssetManager();
$assets->useScript('webcomponent.field-subform');

$main       = ['text', 'value'];
$additional = [];
$hidden     = [];
foreach ($tmpl->getGroup('') as $field)
{
	$fieldname = $field->__get('fieldname');
	$group     = $field->__get('group');
	if (strtolower($field->__get('type')) === 'hidden')
	{
		$hidden[$fieldname] = $group;
	}
	elseif (!in_array($fieldname, $main))
	{
		$additional[$fieldname] = $group;
	}
}

?>
<div class="subform-repeatable-wrapper subform-table-layout subform-table-sublayout-section">
	<joomla-field-subform class="subform-repeatable" name="<?php echo $name; ?>"
						  button-add=".group-add"
						  button-remove=".group-remove"
						  button-move=".group-move"
						  repeatable-element=".subform-repeatable-group"
						  rows-container="tbody.subform-repeatable-container"
						  minimum="<?php echo $min; ?>"
						  maximum="1000">
		<div class="table-responsive">
			<table class="table" id="subfieldList_<?php echo $fieldId; ?>">
				<caption class="visually-hidden">
					<?php echo Text::_('JGLOBAL_REPEATABLE_FIELDS_TABLE_CAPTION'); ?>
				</caption>
				<thead>
				<tr>
					<?php foreach ($main as $field): ?>
						<th scope="col">
							<?php echo Text::_($tmpl->getFieldAttribute($field, 'label')); ?>
						</th>
					<?php endforeach; ?>
					<td style="width:8%;">
						<div class="btn-group">
							<button type="button" class="group-add btn btn-sm btn-success"
									aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
								<span class="icon-plus" aria-hidden="true"></span>
							</button>
						</div>
					</td>
				</tr>
				</thead>
				<tbody class="subform-repeatable-container">
				<?php foreach ($forms as $k => $form)
				{
					echo trim(LayoutHelper::render('plugins.radicalmart_fields.standard.field.subform.option', [
						'form'       => $form,
						'baseGroup'  => $fieldname,
						'group'      => $fieldname . $k,
						'main'       => $main,
						'additional' => $additional,
						'hidden'     => $hidden,
					]));
				} ?>
				</tbody>
			</table>
		</div>
		<template class="subform-repeatable-template-section hidden">
			<?php echo trim(LayoutHelper::render('plugins.radicalmart_fields.standard.field.subform.option', [
				'form'       => $tmpl,
				'baseGroup'  => $fieldname,
				'group'      => $fieldname . 'X',
				'main'       => $main,
				'additional' => $additional,
				'hidden'     => $hidden,
			])); ?>
		</template>
	</joomla-field-subform>
</div>