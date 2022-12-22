<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     1.2.0
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;


extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  object $field  Field data object.
 * @var  array  $values Field values.
 *
 */
?>
<div class="row row-cols-md-6">
	<?php foreach ($values as $value): ?>
		<div>
			<?php if ($src = $value['image'])
			{
				echo HTMLHelper::image($src, htmlspecialchars($value['text']));
			}
			else
			{
				echo '<span class="uk-label">' . $value['text'] . '</span>';
			} ?>
		</div>
	<?php endforeach; ?>
</div>