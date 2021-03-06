<?php

namespace cbednarski\Pharcc\Command;

use cbednarski\Pharcc\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends Command
{
    protected function configure()
    {
        $this->setName('init');
        $this->setDescription('Create a pharcc.yml file in the specified directory');
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'pharcc folder (defaults to the current directory if not specified)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');

        if (!$directory) {
            $directory = getcwd();
        }

        if (Config::generate($directory)) {
            $output->writeln('<info>Initialized pharcc.yml under '.$directory.'</info>');
        } else {
            $output->writeln('<error>Unable to write pharcc.yml to '.$directory.'</error>');
        }
    }
}
