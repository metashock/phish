<?php

class Phish_Command_Info extends Phish_Command
{

    /**
     * Runs the command
     */
    public function run() {
        if(!isset($this->argv[2])) {
            self::usage();
        }

        $search = $this->argv[2];
        $index = Phish_Index::load('phish_info');
        $entry = $index->findclass($search);

        $configuration = new Jm_Configuration_Xmlfile('phish.xml');

        // If we found a configuration file in the current folder
        // and it contains a monitoring section then add the monitored
        // paths to autoload path to find custom project classes
        //
        // @TODO shouldn't we use the index here?
        if($configuration->has('monitor')) {
            foreach($configuration->monitor->path as $path) {
                if($path->has('prefix')) {
                    $prefix = $path->prefix;
                } else {
                    $prefix = '';
               }
               Jm_Autoloader::singleton()->addPath($path, $prefix);
            } 
        }

        if(!is_null($entry)) {
            require_once $entry;
        }

        try {
            $renderer = new Phish_Renderer_Console();
            if(class_exists($search)) {
                $renderer->displayClass(new ReflectionClass($search), $entry);
            } else if (function_exists($search)) {
                $renderer->displayFunction(new ReflectionFunction($search));
            } else if ($funcs = $this->findFuncs($search)) {
                foreach($funcs as $func) {
                    $renderer->displayFunction($func);
                }
            } else { 
                $renderer->displayElementNotFound($search);
            }

        } catch (Phish_Command_Info_BadRegexException $e) {
            $renderer->displayFatal($e->getMessage());
            $this->terminate(1);
        }
    }


    /**
     * Finds internal or user defined functions based on a regex
     * Returns an array with ReflectionFunction objects or NULL
     * if no functions were found.
     *
     * @return array|NULL
     */
    public function findFuncs($needle) {
        $result = array();
        $funcs = get_defined_functions();

        foreach(array('internal', 'user') as $key) {
            foreach($funcs[$key] as $f) {
                $ret = @preg_match('~' . $needle . '~', $f);
                switch(TRUE) {
                    case $ret === FALSE :
                        $error = error_get_last();
                        if($error) {
                            $msg = $error['message'];
                        } else {
                            $msg = 'Unknown error';
                        }
                        throw new Phish_Command_Info_BadRegexException($msg);

                    // function was found :
                    case $ret === 1 :
                        $result []= new ReflectionFunction($f);
                }
            }
        }
        return empty($result) ? NULL : $result;
    }


    public static function shortdesc() {
        return 'Displays reflection information about a class or function';
    }


    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('phish info SEARCH');
        $console->writeln();
        $console->write('SEARCH ', 'bold');
        $console->writeln('can be a class name or a function name.');
        $console->writeln();
        exit(1);
    }
}
