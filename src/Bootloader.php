<?php

namespace Exordium;

class Bootloader
{

    private array $modules;

    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    public function run()
    {
        $this->initModules();
    }

    private function initModules()
    {
        foreach ($this->modules as $name => $class) :
            $this->modules[$name] = new $class;
        endforeach;

    }

    public function getModule(string $module)
    {
        return $this->modules[$module];
    }

}