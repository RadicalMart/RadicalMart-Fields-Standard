<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     1.2.5
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $id             DOM id of the field.
 * @var   string  $label          Label of the field.
 * @var   string  $name           Name of the input field.
 * @var   string  $value          Value attribute of the field.
 * @var   array   $checkedOptions Options that will be set as checked.
 * @var   boolean $hasValue       Has this field a value assigned?
 * @var   array   $options        Options available for this field.
 * @var   string  $onchange       Onchange attribute for the field.
 */
?>
<div id="<?php echo $id; ?>" class="radicalmart-fields-standard-filter_checkboxes">
	<ul class="list-unstyled">
		<?php foreach ($options as $i => $option) : ?>
			<?php
			$checked = in_array((string) $option->value, $checkedOptions) ? 'checked' : '';
			$checked = (!$hasValue && $option->checked) ? 'checked' : $checked;

			$oid   = $id . $i;
			$value = htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8');
			?>
			<li>
				<div class="form-check">
					<input id="<?php echo $oid; ?>" name="<?php echo $name ?>" type="checkbox"
						   class="form-check-input" <?php echo $checked; ?>
						   value="<?php echo $value; ?>"
						<?php if (!empty($onchange)) echo 'onChange="' . $onchange . '"'; ?>>
					<label for="<?php echo $oid; ?>" class="form-check-label">
						<?php echo $option->text; ?>
					</label>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="text-end">
		<a href="javascript:void(0);" class="small text-danger text-lowercase text-decoration-none"
		   onclick="this.closest('.radicalmart-fields-standard-filter_checkboxes').querySelectorAll('input')
			  .forEach(function (input) {input.checked = false; input.dispatchEvent(new Event('change'));});">
			<?php echo Text::_('PLG_RADICALMART_FIELDS_STANDARD_CLEAN'); ?>
		</a>
	</div>
</div>