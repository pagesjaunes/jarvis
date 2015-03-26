<?php

namespace Jarvis\Process;

trait PhpCsFixerAwareTrait
{
    private $phpCsFixer;

    /**
     * Gets the value of phpCsFixer.
     *
     * @return mixed
     */
    public function getPhpCsFixer()
    {
        if (null === $this->phpCsFixer) {
            throw new \RuntimeException('Php CS fixer service is not injected');
        }
        return $this->phpCsFixer;
    }

    /**
     * Sets the value of phpCsFixer.
     *
     * @param mixed $phpCsFixer the php cs fixer
     *
     * @return self
     */
    public function setPhpCsFixer($phpCsFixer)
    {
        $this->phpCsFixer = $phpCsFixer;

        return $this;
    }
}
