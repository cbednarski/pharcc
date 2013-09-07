<?php

namespace cbednarski\Pharcc\Command;

use cbednarski\Pharcc\Config;
use cbednarski\Pharcc\Compiler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends Command
{
    protected function configure()
    {
        $this->setName('build');
        $this->setDescription('Build a phar from a pharcc.yml file in the specified directory');
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

        $config = Config::loadFile($directory . '/pharcc.yml');
        $compiler = new Compiler($config);
        $compiler->setOutput($output);

        $output->writeln('<info>Building ' . $config->getTarget() . '</info>');
        $compiler->build();
        $output->writeln('<info>Build complete.</info>');
    }
}
