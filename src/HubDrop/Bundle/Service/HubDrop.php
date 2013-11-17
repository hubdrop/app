<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use HubDrop\Bundle\Project\Project;

class HubDrop {

    /**
     * Get a Project Object
     */
    public function getProject($name){
       return new Project($name);
    }
}

