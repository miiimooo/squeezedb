<?php

namespace Drupal\squeezedb\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnpublishedCommand.
 *
 * @DrupalCommand (
 *     extension="squeezedb",
 *     extensionType="module"
 * )
 */
class UnpublishedCommand extends Command
{
    /**
     * Drupal\Core\Entity\EntityTypeManagerInterface definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new UnpublishedCommand object.
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
            ->setName('squeezedb:unpublished')
            ->setDescription($this->trans('commands.squeezedb.unpublished.description'))
            ->addOption(
                'info',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.squeezedb.unpublished.options.info')
            )
            ->setAliases(['sdbu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $unpublished = $this->entityTypeManager->getStorage('node')
            ->loadByProperties([
                'status' => 0,
            ]);

        if ($unpublished) {
            try {
                foreach ($unpublished as $node) {
                    if ($input->getOption('info')) {
                        $this->getIo()->info('deleting unpublished node : ' . $node->id());
                    }
                    $node->delete();
                }
                $this->getIo()->info($this->trans('commands.squeezedb.unpublished.messages.success'));

            } catch (Exception $e) {
                $this->getIo()->info($this->trans('commands.squeezedb.unpublished.messages.error'));
            }
        } else {
            $this->getIo()->info($this->trans('commands.squeezedb.unpublished.messages.notfound'));
        }
    }
}
