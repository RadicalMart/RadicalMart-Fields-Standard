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

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $form       The form instance for render the section.
 * @var   string $baseGroup  The base group name.
 * @var   string $group      Current group name.
 * @var   array  $main       Array of the main fields names.
 * @var   array  $additional Array of the additional fields names=>group.
 * @var   array  $hidden     Array of the hidden fields like names=>group.
 */
?>
<tr class="subform-repeatable-group" data-base-name="<?php echo $baseGroup; ?>" data-group="<?php echo $group; ?>">
	<?php foreach ($main as $name) : ?>
		<td data-column="<?php echo strip_tags($name); ?>">
			<?php echo $form->renderField($name, null, null,
				['hiddenLabel' => true, 'hiddenDescription' => true]); ?>
			<?php if ($name === 'text'): ?>
				<?php if (!empty($additional)): ?>
					<div>
						<button class="btn btn-outline-info btn-sm" type="button"
						        onclick="this.nextElementSibling.classList.toggle('show')">
							<?php echo Text::_('PLG_RADICALMART_FIELDS_STANDARD_OPTIONS_ADDITIONAL'); ?>
						</button>
						<div class="collapse mt-1">
							<?php foreach ($additional as $fieldName => $fieldGroup)
							{
								echo $form->renderField($fieldName, $fieldGroup);
							} ?>
						</div>
					</div>
				<?php endif; ?>
				<?php if (!empty($hidden)): ?>
					<div class="hidden">
						<?php foreach ($hidden as $fieldName => $fieldGroup)
						{
							echo $form->getInput($fieldName, $fieldGroup);
						} ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	<?php endforeach; ?>
	<td>
		<div class="btn-group">
			<button type="button" class="group-add btn btn-sm btn-success"
			        aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
				<span class="icon-plus" aria-hidden="true"></span>
			</button>
			<button type="button" class="group-remove btn btn-sm btn-danger"
			        aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
				<span class="icon-minus" aria-hidden="true"></span>
			</button>
			<button type="button" class="group-move btn btn-sm btn-primary"
			        aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
				<span class="icon-arrows-alt" aria-hidden="true"></span>
			</button>
		</div>
	</td>
</tr>
