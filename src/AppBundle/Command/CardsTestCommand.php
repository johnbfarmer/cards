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

class CardsTestCommand extends ContainerAwareCommand
{
    protected 
        $config,
        $connection,
        $parameters;

    protected function configure()
    {
        $this->setName('cards:hearts')
            ->setDescription('')
            ->setHelp('')
            ->addArgument('game', InputArgument::OPTIONAL, '', 'hearts')
            ->addOption('players', 'p', InputOption::VALUE_REQUIRED, 'number of players', 4)
            ->addOption('cards', 'c', InputOption::VALUE_REQUIRED, 'number of cards', 13)
            ->addOption('no-prompt', null, InputOption::VALUE_NONE, 'wait for user input');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parameters = array_merge($input->getArguments(), $input->getOptions());
        $io = new SymfonyStyle($input, $output);

        $io->title("The game is " . strtoupper($this->parameters['game']));

        $this->parameters['output'] = $output;
        $t = new Game($this->parameters);
        $t->play();
    }
}
