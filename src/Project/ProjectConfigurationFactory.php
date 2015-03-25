<?php

namespace Jarvis\Project;

class ProjectConfigurationFactory
{
    /**
     * @var string
     */
    private $localProjectsRootDir;

    /**
     * @var string
     */
    private $localVendorRootDir;

    /**
     * @var string
     */
    private $localCdnRootDir;

    /**
     * @var string
     */
    private $remoteVendorRootDir;

    /**
     * @var string
     */
    private $remoteProjectsRootDir;

    public function __construct(
        $localProjectsRootDir,
        $remoteProjectsRootDir,
        $localVendorRootDir,
        $localCdnRootDir,
        $remoteVendorRootDir
    ) {
        $this->localProjectsRootDir = $localProjectsRootDir;
        $this->remoteProjectsRootDir = $remoteProjectsRootDir;
        $this->localVendorRootDir = $localVendorRootDir;
        $this->localCdnRootDir = $localCdnRootDir;
        $this->remoteVendorRootDir = $remoteVendorRootDir;
    }

    public function create(array $data)
    {
        return new ProjectConfiguration(
            $data,
            $this->localProjectsRootDir,
            $this->remoteProjectsRootDir,
            $this->localVendorRootDir,
            $this->localCdnRootDir,
            $this->remoteVendorRootDir
        );
    }
}
