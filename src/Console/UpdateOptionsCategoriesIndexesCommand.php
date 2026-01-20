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

namespace Joomla\Plugin\RadicalMartFields\Standard\Console;

\defined('_JEXEC') or die;

use Joomla\Component\RadicalMart\Administrator\Console\AbstractCommand;
use Joomla\Component\RadicalMart\Administrator\Helper\CommandsHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Symfony\Component\Console\Input\InputInterface;

class UpdateOptionsCategoriesIndexesCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  2.1.0
	 */
	protected static $defaultName = 'radicalmart:fields:standard:update_options_categories_indexes';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  2.1.0
	 */
	protected string $commandText = 'RadicalMart Fields - Standard: Create Options Categories Indexes';

	/**
	 * Command description for configure help block.
	 *
	 * @var   string
	 *
	 * @since  2.1.0
	 */
	protected string $commandDescription = 'Method to massive scan and create field options categories indexes';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  2.1.0
	 */
	protected array $methods = [
		'executeCommand',
	];

	/**
	 * Fields aliases array
	 * @var array|null
	 *
	 * @since 2.1.0
	 */
	protected ?array $_fieldsMapping = null;

	/**
	 * Method to find and delete carts or/and send notifications.
	 *
	 * @throws \Exception
	 *
	 * @since 2.1.0
	 */
	protected function executeCommand(InputInterface $input): void
	{
		$this->ioStyle->title('RadicalMart Fields - Standard: Update Options Categories Indexes');
		$this->getFieldsMapping();
		if (count($this->_fieldsMapping) === 0)
		{
			$this->ioStyle->note('There are no fields defined. Task complete!');

			return;
		}

		$this->cleanIndexes();
		$this->createIndexes();
	}

	/**
	 * Method to get fields.
	 *
	 * @since 2.1.0
	 */
	protected function getFieldsMapping(): void
	{
		$this->ioStyle->text('Get fields mapping');
		$this->startProgressBar(1, true);

		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select(['id', 'alias', 'params'])
			->from($db->quoteName('#__radicalmart_fields'))
			->where($db->quoteName('plugin') . ' = ' . $db->quote('standard'));
		$rows  = $db->setQuery($query)->loadObjectList();

		$this->_fieldsMapping = [];
		foreach ($rows as $row)
		{
			$row->params = new Registry($row->params);
			$type        = $row->params->get('type');
			if (empty($type) || !in_array($type, ['list', 'checkboxes'])
				|| (int) $row->params->get('display_filter', 0) === 0
			)
			{
				continue;
			}

			$this->_fieldsMapping[$row->alias] = $row->id;
		}

		$db->disconnect();
		$this->finishProgressBar();
	}

	/**
	 * Method to clean options Indexes.
	 *
	 * @since 2.1.0
	 */
	protected function cleanIndexes(): void
	{
		$this->ioStyle->text('Clean fields indexes');
		$this->startProgressBar(count($this->_fieldsMapping), true);

		$db = $this->getDatabase();
		foreach ($this->_fieldsMapping as $pk)
		{
			$query  = $db->createQuery()
				->select(['id', 'options'])
				->from($db->quoteName('#__radicalmart_fields'))
				->where($db->quoteName('id') . ' = :pk')
				->bind(':pk', $pk, ParameterType::INTEGER);
			$update = $db->setQuery($query, 0, 1)->loadObject();

			$update->options = new Registry($update->options);
			foreach (array_keys($update->options->toArray()) as $key)
			{
				$update->options->remove($key . '.categories');
				$update->options->set($key . '.option_categories', '');
			}
			$update->options = $update->options->toString();

			$db->updateObject('#__radicalmart_fields', $update, 'id');
		}

		$db->disconnect();
		$this->finishProgressBar();
	}

	/**
	 * Method to add indexes.
	 *
	 * @since 2.1.0
	 */
	protected function createIndexes(): void
	{
		$this->ioStyle->text('Get products total');
		$this->startProgressBar(1, true);
		$total = CommandsHelper::getTotalItems('#__radicalmart_products');
		$this->finishProgressBar();

		if ($total === 0)
		{
			$this->ioStyle->note('There are no Products defined. Task complete!');
		}

		$this->ioStyle->text('Create indexes');
		$this->startProgressBar($total, true);

		$db    = $this->getDatabase();
		$limit = 100;
		$last  = 0;
		while (true)
		{
			$query    = $db->createQuery()
				->select(['id', 'categories_all', 'fields'])
				->from($db->quoteName('#__radicalmart_products'))
				->where($db->quoteName('id') . ' > :last')
				->bind(':last', $last, ParameterType::INTEGER)
				->order('id');
			$products = $db->setQuery($query, 0, $limit)->loadObjectList();
			$count    = count($products);
			if ($count === 0)
			{
				break;
			}

			$fields = [];
			foreach ($products as $product)
			{
				$last = (int) $product->id;

				$product->fields = (new Registry($product->fields))->toArray();
				$categories      = ArrayHelper::toInteger(explode(',', $product->categories_all));
				foreach ($product->fields as $alias => $values)
				{
					if (!isset($this->_fieldsMapping[$alias]))
					{
						continue;
					}

					$filed_id = $this->_fieldsMapping[$alias];
					$values   = (is_array($values)) ? $values : [$values];
					foreach ($values as $value)
					{
						if (is_scalar($value) && (string) $value === '')
						{
							continue;
						}

						if (!isset($fields[$filed_id]))
						{
							$fields[$filed_id] = [];
						}

						if (!isset($fields[$filed_id][$value]))
						{
							$fields[$filed_id][$value] = [];
						}

						$fields[$filed_id][$value] = array_unique(array_merge($fields[$filed_id][$value], $categories));
					}
				}
				$this->advanceProgressBar();
			}

			$this->saveIndexes($fields);


			if ($count < $limit)
			{
				break;
			}
		}

		$this->finishProgressBar();
	}

	/**
	 * Method to save indexes.
	 *
	 * @param   array  $fields  Fields options indexes.
	 *
	 * @since 2.1.0
	 */
	protected function saveIndexes(array $fields = []): void
	{
		$db = $this->getDatabase();
		if (count($fields) === 0)
		{
			$db->disconnect();

			return;
		}

		$query = $db->getQuery(true)
			->select(['id', 'options'])
			->from($db->quoteName('#__radicalmart_fields'))
			->whereIn($db->quoteName('id'), array_keys($fields));
		$rows  = $db->setQuery($query)->loadObjectList();
		if (count($rows) === 0)
		{
			$db->disconnect();

			return;
		}

		foreach ($rows as $update)
		{
			$update->options = new Registry($update->options);
			$indexes         = $fields[$update->id];
			$needUpdate      = false;
			foreach ($indexes as $path => $index)
			{
				if (count($index) === 0)
				{
					continue;
				}
				if (!$update->options->exists($path))
				{
					continue;
				}

				$full_path      = $path . '.option_categories';
				$current_string = $update->options->get($full_path, '');
				$current        = (!empty($current_string)) ? ArrayHelper::toInteger(explode(',', $current_string)) : [];
				$index          = ArrayHelper::toInteger($index);
				$merge          = array_unique(array_merge($current, $index));

				if (count($merge) === count($current))
				{
					continue;
				}

				$update->options->set($full_path, implode(',', $merge));
				$needUpdate = true;
			}

			if ($needUpdate)
			{
				$update->options = $update->options->toString();

				$db->updateObject('#__radicalmart_fields', $update, 'id');
			}
		}

		$db->disconnect();
	}
}