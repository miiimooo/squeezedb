<?php

namespace Drupal\squeezedb\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UndesiredRevisionCommand.
 *
 * @DrupalCommand (
 *     extension="squeezedb",
 *     extensionType="module"
 * )
 */
class UndesiredRevisionCommand extends Command
{
    /**
     * Drupal\Core\Entity\EntityTypeManagerInterface definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new UndesiredRevisionCommand object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('squeezedb:undesired_revision')
            ->setDescription($this->trans('commands.squeezedb.undesired_revision.description'))
            ->addOption(
                'entity-type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.squeezedb.undesired_revision.options.entity_type')
            )
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.squeezedb.undesired_revision.options.bundle')
            )
            ->addOption(
                'retain',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.squeezedb.undesired_revision.options.retain')
            )
            ->addOption(
                'info',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.squeezedb.undesired_revision.options.info')
            )
            ->setAliases(['sdbur']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity_type = $input->getOption('entity-type');
        $bundle = $input->getOption('bundle');
        $retain = $input->getOption('retain');
        $info = $input->getOption('info');

        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $query = $storage
            ->getQuery()
            ->condition('type', $bundle)
            ->accessCheck(false)
            ->execute();
        $entity_ids = array_values($query);

        if ($entity_ids) {
            try {
                foreach (array_chunk($entity_ids, 50) as $eid_chunk) {
                    $entities = $storage->loadMultiple($eid_chunk);
                    foreach ($entities as $entity) {
                        $this->clean_up_database_purge_revisions($entity, $entity->getRevisionId(), $retain, $info);
                    }
                }
                $this->getIo()->info($this->trans('commands.squeezedb.undesired_revision.messages.success'));
            } catch (Exception $e) {
                $this->getIo()->info($this->trans('commands.squeezedb.undesired_revision.messages.error'));
            }
        } else {
            $this->getIo()->info($this->trans('commands.squeezedb.undesired_revision.messages.notfound'));
        }
    }

    private function clean_up_database_purge_revisions(EntityInterface $entity, $current_revision_id, $retain, $info)
    {
        $entity_type = $entity->getEntityType();
        $revision_key = $entity_type->getKey('revision');
        $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $query = $entity_storage
            ->getQuery()
            ->allRevisions()
            ->sort($revision_key, 'ASC')
            ->condition($entity_type->getKey('id'), $entity->id());

        $revisions = array_keys($query->execute());
        $revisions = array_diff($revisions, [$current_revision_id]);

        if (count($revisions) > $retain) {
            $revisions = array_slice($revisions, 0, count($revisions) - $retain);
        }
        if (is_array($revisions) && (count($revisions) >= $retain)) {
            foreach ($revisions as $revision_id) {
                if ($info) {
                    $this->getIo()->info('deleting undesired revision for ' . $entity_type->id() . ' : ' . $entity->id() . ' revision : ' . $revision_id);
                }
                $entity_storage->deleteRevision($revision_id);
            }
        }
    }
}
