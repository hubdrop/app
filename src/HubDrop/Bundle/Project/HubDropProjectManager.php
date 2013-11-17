<?php
namespace HubDrop\Bundle\Project;

use HubDrop\Bundle\HubDrop;

class HubDropProjectManager
{
    protected $project;

    public function __construct(HubDropProject $project)
    {
        $this->project = $project;
    }
