<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'WEC Map',
    'description' => 'Mapping extension that connects to geocoding databases and Google Maps API.',
    'category' => 'plugin',
    'shy' => 0,
    'version' => '4.1.6',
    'priority' => 'bottom',
    'loadOrder' => '',
    'module' => 'mod1,mod2',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'Web-Empowered Church Team (V1.x, V2.x), Jan Bartels (V3.x)',
    'author_email' => 'j.bartels@arcor.de',
    'author_company' => 'Christian Technology Ministries International Inc. (V1.x, V2.x)',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.0-9.9.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'tt_address'         => '3.2.0-0.0.0',
            'nn_address'         => '2.3.0-0.0.0',
            'static_info_tables' => '6.4.0-0.0.0',
        ],
    ],
    'autoload' => [
        'psr-4' => [
              'JBartels\\WecMap\\' => 'Classes',
        ],
    ],
];
