<?php

namespace Drupal\squeezedb\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Drupal\Core\Database\Connection;

/**
 * Class TruncateTableCommand.
 *
 * @DrupalCommand (
 *     extension="squeezedb",
 *     extensionType="module"
 * )
 */
class TruncateTableCommand extends Command
{
    /**
     * \Drupal\Core\Database\Connection definition.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Constructs a new TruncateTableCommand object.
     *
     * @param \Drupal\Core\Database\Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('squeezedb:truncate')
            ->setDescription($this->trans('commands.squeezedb.truncate.description'))
            ->addOption(
                'table',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.squeezedb.truncate.options.table')
            )
            ->setAliases(['sdbt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->database->schema()->tableExists($input->getOption('table'))) {
            try {
                $this->database->truncate($input->getOption('table'))->execute();
                $this->getIo()->info($this->trans('commands.squeezedb.truncate.messages.success'));
            } catch (\Exception $e) {
                $this->getIo()->info($this->trans('commands.squeezedb.truncate.messages.error'));
            }
        } else {
            $this->getIo()->info($this->trans('commands.squeezedb.truncate.messages.notfound'));
        }
    }
}
