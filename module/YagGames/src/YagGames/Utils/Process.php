<?php

namespace YagGames\Utils;

use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    protected $scriptName;

    public function __construct($request)
    {
        $this->scriptName = $request->getServer()->get('SCRIPT_FILENAME');
    }

    public function start($command)
    {
        $process = new SymfonyProcess('php ' . $this->scriptName . ' ' . $command);
        $process->start();
    }
    
    public function startFullCommand($command)
    {
        $process = new SymfonyProcess('php ' . $command);
        $process->run();
    }

}
