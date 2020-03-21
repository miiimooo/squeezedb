<?php

namespace Drupal\squeezedb\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OrphanParagraphCommand.
 *
 * @DrupalCommand (
 *     extension="squeezedb",
 *     extensionType="module"
 * )
 */
class OrphanParagraphCommand extends Command
{
    /**
     * Drupal\Core\Entity\EntityTypeManagerInterface definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * \Drupal\Core\Database\Connection definition.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * @package Drupal\squeezedb
     *
     * @DrupalCommand (
     *     extension="squeezedb",
     *     extensionType="module"
     * )
     * Constructs a new OrphanParagraphCommand object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     * @param \Drupal\Core\Database\Connection $database
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('squeezedb:orphan_paragraph')
            ->setDescription($this->trans('commands.squeezedb.orphan_paragraph.description'))
            ->addOption(
                'info',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.squeezedb.orphan_paragraph.options.info')
            )
            ->setAliases(['sdbop']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $this->database->select('paragraphs_item_field_data', 'pfd')
            ->fields('pfd', ['id'])
            ->condition('pfd.parent_type', 'node');
        $query->addJoin('left', 'node', 'n', 'pfd.parent_id=n.nid');
        $query->isNull('n.nid');
        $query->distinct();

        $paragraph_ids = $query->execute()->fetchCol();

        if ($paragraph_ids) {
            try {
                $para_storage = $this->entityTypeManager->getStorage('paragraph');
                foreach ($paragraph_ids as $paragraph_id) {
                    if ($para = $para_storage->load($paragraph_id)) {
                        if ($input->getOption('info')) {
                            $this->getIo()->info('deleting orphan paragraph : ' . $para->id());
                        }
                        $para->delete();
                    }
                }
                $this->getIo()->info($this->trans('commands.squeezedb.orphan_paragraph.messages.success'));
            } catch (Exception $e) {
                $this->getIo()->info($this->trans('commands.squeezedb.orphan_paragraph.messages.error'));
            }
        } else {
            $this->getIo()->info($this->trans('commands.squeezedb.orphan_paragraph.messages.notfound'));
        }
    }
}
