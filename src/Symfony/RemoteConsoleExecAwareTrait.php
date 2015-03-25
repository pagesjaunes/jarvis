<?php

namespace Jarvis\Symfony;

use Jarvis\Symfony\RemoteConsoleExec as SymfonyRemoteConsoleExec;

trait RemoteConsoleExecAwareTrait
{
    /**
     * @var SymfonyRemoteConsoleExec
     */
    private $symfonyRemoteConsoleExec;

    /**
     * Sets the value of symfonyRemoteConsoleExec.
     *
     * @param SymfonyRemoteConsoleExec $symfonyRemoteConsoleExec the symfony remote console exec
     *
     * @return self
     */
    public function setSymfonyRemoteConsoleExec(SymfonyRemoteConsoleExec $symfonyRemoteConsoleExec)
    {
        $this->symfonyRemoteConsoleExec = $symfonyRemoteConsoleExec;

        return $this;
    }

    /**
     * Gets the value of symfonyRemoteConsoleExec.
     *
     * @return SymfonyRemoteConsoleExec
     */
    protected function getSymfonyRemoteConsoleExec()
    {
        return $this->symfonyRemoteConsoleExec;
    }
}
