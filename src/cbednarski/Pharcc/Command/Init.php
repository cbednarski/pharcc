<?php

namespace cbednarski\Pharcc\Command;

use cbednarski\Pharcc\Config;
use cbednarski\Pharcc\Compiler;
// use cbednarski\Pharcc\FileUtils;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

        $pharccyml = file_get_contents(__DIR__ . '/../Resources/pharcc.yml');

        if(is_writeable($directory)) {
            file_put_contents($directory . DIRECTORY_SEPARATOR . 'pharcc.yml', $pharccyml);
            $output->writeln('<info>Initialized pharcc.yml under '.$directory.'</info>');
        } else {
            $output->writeln('<error>Unable to write pharcc.yml to '.$directory.'</error>');
        }
    }
}
