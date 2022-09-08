<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

use AppBundle\Cards\Game;

class TestCommand extends ContainerAwareCommand
{
    protected 
        $config,
        $connection,
        $parameters;

    protected function configure()
    {
        $this->setName('cards:test')
            ->setDescription('')
            ->setHelp('')
            ->addArgument('game', InputArgument::OPTIONAL, '', 'hearts')
            ->addOption('players', 'p', InputOption::VALUE_REQUIRED, 'number of players', 4)
            ->addOption('cards', 'c', InputOption::VALUE_REQUIRED, 'number of cards', 13);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('CARDS');
        $this->parameters = array_merge($input->getArguments(), $input->getOptions());
        $output->writeln(strtoupper($this->parameters['game']));
        $input->setInteractive(true);
        $io = new SymfonyStyle($input, $output);

        $io->title("The game is " . strtoupper($this->parameters['game']));

        $this->parameters['output'] = $output;
        $t = new Game($this->parameters);
        $t->start();
        $a = $io->ask(
            "Press ENTER to continue, q to quit",
            null,
            function($answer) { return strtolower($answer) !== 'q'; }
        );

        while($a && $t->play()) {
            $a = $io->ask(
                "Press ENTER to continue, q to quit",
                null,
                function($answer) { return strtolower($answer) !== 'q'; }
            );
            // break;
        }
    }
}
