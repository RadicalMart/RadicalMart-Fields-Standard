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

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
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
<div id="<?php echo $id; ?>" class="radicalmart-fields-standard-filter_images">
	<div class="uk-thumbnav uk-margin-remove">
		<?php foreach ($options as $i => $option): ?>
			<?php
			$checked = in_array((string) $option->value, $checkedOptions) ? 'checked' : '';
			$checked = (!$hasValue && $option->checked) ? 'checked' : $checked;
			$active  = ($checked) ? 'btn-info' : 'btn-outline-info';
			$oid     = $id . $i;
			$value   = htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8');
			?>
			<label for="<?php echo $oid; ?>" title="<?php echo htmlspecialchars($option->text); ?>"
				   class="btn btn-sm mb-1 <?php echo $active; ?>">
				<?php if ($src = $option->image)
				{
					echo HTMLHelper::image($src, htmlspecialchars($option->text));
				}
				else
				{
					echo '<span class="badge">' . $option->text . '</span>';
				} ?>
				<input id="<?php echo $oid; ?>" name="<?php echo $name ?>" type="checkbox"
					   class="d-none" <?php echo $checked; ?>
					   value="<?php echo $value; ?>"
					   onchange="if(this.checked) {
						   this.closest('label').classList.add('btn-info');
						   this.closest('label').classList.remove('btn-outline-info');
					   } else {
						    this.closest('label').classList.add('btn-outline-info');
						   this.closest('label').classList.remove('btn-info');
					   } this.closest('label').blur();
						   <?php if (!empty($onchange)) echo $onchange; ?>">
			</label>
		<?php endforeach; ?>
	</div>
	<div class="text-end">
		<a href="javascript:void(0);" class="small text-danger text-lowercase text-decoration-none"
			  onclick="this.closest('.radicalmart-fields-standard-filter_images').querySelectorAll('input')
			  .forEach(function (input) {input.checked = false; input.dispatchEvent(new Event('change'));});">
			<?php echo Text::_('PLG_RADICALMART_FIELDS_STANDARD_CLEAN'); ?>
		</a>
	</div>
</div>