<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Panopticon\CliCommand;

defined('AKEEBA') || die;

use Akeeba\Panopticon\CliCommand\Trait\ForkedLoggerAwareTrait;
use Akeeba\Panopticon\Factory;
use Akeeba\Panopticon\Library\Task\CallbackInterface;
use Akeeba\Panopticon\Library\Task\Status;
use Akeeba\Panopticon\Task\LogRotate as LogRotateTask;
use Awf\Registry\Registry;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: "site:update:joomla",
	description: "Updates Joomla core"
)]
class JoomlaUpdate extends AbstractCommand
{
	use ForkedLoggerAwareTrait;

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/** @var LogRotateTask|CallbackInterface $callback */
		$container = Factory::getContainer();
		$callback  = $container->taskRegistry->get('joomlaupdate');

		if ($callback instanceof LoggerAwareInterface)
		{
			$callback->setLogger(
				$this->getForkedLogger(
					$output,
					[
						$container->loggerFactory->get('joomla_update'),
					]
				)
			);
		}

		$dummy    = new \stdClass();
		$registry = new Registry();

		$dummy->site_id = $input->getArgument('id');

		do
		{
			$return = $callback($dummy, $registry);
		} while ($return === Status::WILL_RESUME->value);

		return Command::SUCCESS;
	}

	protected function configure(): void
	{
		$this
			->addArgument('id', InputArgument::REQUIRED, 'Site ID to update')
			->addOption('force', 'f', InputOption::VALUE_NEGATABLE, 'Force update, regardless of last update time', false)
			->addOption('batchSize', null, InputOption::VALUE_OPTIONAL, 'Number of sites to retrieve concurrently', 10);
	}

}