<?php

/*
 * This file is part of the Jarvis package
 *
 * Copyright (c) 2015 Tony Dubreil
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Tony Dubreil <tonydubreil@gmail.com>
 */

namespace Jarvis\Project;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectConfiguration
{
    /**
     * Data.
     *
     * @var array
     */
    private $data;

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
    private $remoteVendorRootDir;

    /**
     * @var string
     */
    private $localCdnRootDir;

    /**
     * @var string
     */
    private $remoteProjectsRootDir;

    /**
     * @param array  $data
     * @param string $localProjectsRootDir
     * @param string $localVendorRootDir
     * @param string $remoteVendorRootDir
     * @param string $remoteProjectsRootDir
     */
    public function __construct(
        array $data,
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

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->data = $resolver->resolve($data);

        foreach ($this->data as $k => $v) {
            $this->data[$k] = $this->normalize($v);
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('local_assets_dir', null);
        $resolver->setDefault('remote_assets_dir', '%remote_webapp_dir%');
        $resolver->setDefault('tags', []);

        $resolver->setRequired([
            'project_name',
            'git_repository_url',
            'git_target_branch',
            'local_git_repository_dir',
            'remote_git_repository_dir',
            'local_webapp_dir',
            'local_vendor_dir',
            'remote_webapp_dir',
            'remote_vendor_dir',
            'remote_phpunit_configuration_xml_path',
            'remote_symfony_console_path',
        ]);

        $resolver->setAllowedTypes('tags', 'array');
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return is_dir($this->getLocalGitRepositoryDir());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'project_name' => $this->getProjectName(),
            'git_repository_url' => $this->getGitRepositoryUrl(),
            'local_git_repository_dir' => strtr($this->getLocalGitRepositoryDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->localProjectsRootDir => '%local_projects_root_dir%',
            ]),
            'remote_git_repository_dir' => strtr($this->getRemoteGitRepositoryDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->remoteProjectsRootDir => '%remote_projects_root_dir%',
            ]),
            'git_target_branch' => $this->getGitTargetBranch(),
            'remote_webapp_dir' => strtr($this->getRemoteWebappDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->remoteProjectsRootDir => '%remote_projects_root_dir%',
            ]),
            'local_webapp_dir' => strtr($this->getLocalWebappDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->localProjectsRootDir => '%local_projects_root_dir%',
            ]),
            'remote_vendor_dir' => strtr($this->getRemoteVendorDir(), [
                $this->data['project_name'] => '%project_name%',
            ]),
            'local_vendor_dir' => strtr($this->getLocalVendorDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->localVendorRootDir => '%local_vendor_root_dir%',
            ]),
            'remote_symfony_console_path' => strtr($this->getRemoteSymfonyConsolePath(), [
                $this->data['project_name'] => '%project_name%',
                $this->remoteProjectsRootDir => '%remote_projects_root_dir%',
            ]),
            'remote_phpunit_configuration_xml_path' => strtr($this->getRemotePhpunitConfigurationXmlPath(), [
                $this->data['project_name'] => '%project_name%',
                $this->remoteProjectsRootDir => '%remote_projects_root_dir%',
            ]),
            'remote_assets_dir' => strtr($this->getRemoteAssetsDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->remoteProjectsRootDir => '%remote_projects_root_dir%',
            ]),
            'local_assets_dir' => strtr($this->getLocalAssetsDir(), [
                $this->data['project_name'] => '%project_name%',
                $this->localCdnRootDir => '%local_cdn_root_dir%',
            ]),
            'tags' => $this->getTags()
        ];
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        return $this->data['project_name'];
    }

    /**
     * @return string
     */
    public function getTags()
    {
        return $this->data['tags'];
    }

    /**
     * @return string
     */
    public function getLocalWebappDir()
    {
        return $this->data['local_webapp_dir'];
    }

    /**
     * @return string
     */
    public function getRemoteWebappDir()
    {
        return $this->data['remote_webapp_dir'];
    }

    /**
     * @return string
     */
    public function getRemoteSymfonyConsolePath()
    {
        return $this->data['remote_symfony_console_path'];
    }

    /**
     * @return string
     */
    public function getRemotePhpunitConfigurationXmlPath()
    {
        return $this->data['remote_phpunit_configuration_xml_path'];
    }

    /**
     * @return string
     */
    public function getLocalVendorDir()
    {
        return $this->data['local_vendor_dir'];
    }

    /**
     * @return string
     */
    public function getLocalAssetsDir()
    {
        return $this->data['local_assets_dir'];
    }

    /**
     * @return string
     */
    public function getRemoteAssetsDir()
    {
        return $this->data['remote_assets_dir'];
    }

    /**
     * @return string
     */
    public function getRemoteVendorDir()
    {
        return $this->data['remote_vendor_dir'];
    }

    /**
     * @return string
     */
    public function getLocalGitRepositoryDir()
    {
        return $this->data['local_git_repository_dir'];
    }

    /**
     * @return string
     */
    public function getLocalGitHooksDir()
    {
        return sprintf('%s/.git/hooks', $this->data['local_git_repository_dir']);
    }

    /**
     * @return string
     */
    public function getLocalTemporaryCopyStagingAreaDir($name = '.tmp_staging_area')
    {
        return $this->getLocalGitRepositoryDir().'/'.$this->normalize($name);
    }

    /**
     * @return string
     */
    public function getRemoteGitRepositoryDir()
    {
        return $this->data['remote_git_repository_dir'];
    }

    /**
     * @return string
     */
    public function getRemoteTemporaryCopyStagingAreaDir($name = '.tmp_staging_area')
    {
        return $this->getRemoteGitRepositoryDir().'/'.$this->normalize($name);
    }

    /**
     * @return string
     */
    public function getGitRepositoryUrl()
    {
        return $this->data['git_repository_url'];
    }

    /**
     * @return string
     */
    public function getGitTargetBranch()
    {
        return $this->data['git_target_branch'];
    }

    public function normalize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return strtr($value, [
            '%project_name%' => $this->data['project_name'],
            '%local_projects_root_dir%' => $this->localProjectsRootDir,
            '%local_vendor_root_dir%' => $this->localVendorRootDir,
            '%local_cdn_root_dir%' => $this->localCdnRootDir,
            '%remote_vendor_root_dir%' => $this->remoteVendorRootDir,
            '%remote_projects_root_dir%' => $this->remoteProjectsRootDir,
            '%remote_webapp_dir%' => isset($this->data['remote_webapp_dir']) ? $this->data['remote_webapp_dir'] : null,
        ]);
    }
}
