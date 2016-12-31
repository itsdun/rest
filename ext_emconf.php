<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "rest"
 *
 * Auto generated by Extension Builder 2013-05-11
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title'            => 'rest',
    'description'      => 'REST API for TYPO3 CMS',
    'category'         => 'services',
    'author'           => 'Daniel Corn',
    'author_email'     => 'info@cundd.net',
    'author_company'   => 'cundd',
    'shy'              => '',
    'priority'         => '',
    'module'           => '',
    'state'            => 'beta',
    'internal'         => '',
    'uploadfolder'     => '0',
    'createDirs'       => '',
    'modify_tables'    => '',
    'clearCacheOnLoad' => 0,
    'lockType'         => '',
    'version'          => '3.0.0-dev',
    'constraints'      => array(
        'depends'   => array(
            'typo3' => '7.4-8.99.99',
        ),
        'conflicts' => array(),
        'suggests'  => array(
            'cundd_composer' => '2.0',
        ),
    ),
);
