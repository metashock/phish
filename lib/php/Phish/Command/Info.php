<?php

class Phish_Command_Info extends Phish_Command
{

    public function run() {
        if(!isset($this->argv[2])) {
            $this->usage();
        }
        $search = $this->argv[2];
        $index = Phish_Index::load('phish_info');
        $entry = $index->findclass($search);
        if(!is_null($entry)) {
            require_once $entry;
        }

        $renderer = new Phish_Renderer_Console();
        if(class_exists($search)) {
            $renderer->renderClass(new ReflectionClass($search), $entry);
        } else if (function_exists($search)) {
            $renderer->renderFunction(new ReflectionFunction($search));
        } else { 
            $renderer->renderElementNotFound($search);
        }
    }


    public function usage() {
        $console = Jm_Console::singleton();
        $console->writeln('Usage: phish info SEARCH');
        $console->writeln('');
        $console->writeln('SEARCH can be a class name or a function name.');
        exit(1);
    }
}
