<?php
/**
 * This file is part of the SymfonyCronBundle package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cron\CronBundle\Command;

use Cron\CronBundle\Cron\CommandBuilder;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


/**
 * @author Timofey Mikhaylov <amadeus_m@mail.ru>
 */
class CronCommand extends ContainerAwareCommand
{
    /**
     * @var \SplObjectStorage
     */
    private $processes;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cron:cron')
            ->setDescription('Starts cron scheduler')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var CommandBuilder $builder */
        $builder = $this->getContainer()->get('cron.command_builder');
        $command = $builder->build('cron:run');
        $this->processes = new \SplObjectStorage();
        $loop = Factory::create();
        $loop->addPeriodicTimer(60.0,
            function () use ($command) {
                $process = new Process($command);
                $this->processes->attach($process, new \DateTime());
                $process->start();
            }
        );
        $loop->addPeriodicTimer(1.0,
            function () use ($output) {
                /** @var Process $process */
                foreach ($this->processes as $process) {
                    if (!$process->isRunning()) {
                        $output->writeln($process->getOutput());
                        $this->processes->detach($process);
                    }
                }
            }
        );
        $loop->run();
    }
}
