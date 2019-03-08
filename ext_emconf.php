<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "wec_map".
 *
 * Auto generated 23-04-2017 05:05
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
  'title' => 'WEC Map',
  'description' => 'Mapping extension that connects to geocoding databases and Google Maps API.',
  'category' => 'plugin',
  'version' => '4.1.5',
  'state' => 'stable',
  'uploadfolder' => false,
  'createDirs' => '',
  'clearcacheonload' => true,
  'author' => 'Web-Empowered Church Team (V1.x, V2.x], Jan Bartels (V3.x)',
  'author_email' => 'j.bartels@arcor.de',
  'author_company' => 'Christian Technology Ministries International Inc. (V1.x, V2.x)',
  'constraints' =>   [
    'depends' => [
      'php' => '7.0.0-0.0.0',
      'typo3' => '8.7.0-8.9.99',
    ],
    'conflicts' => [
    ],
    'suggests' => [
      'tt_address' => '4.0.0-0.0.0',
      'static_info_tables' => '6.5.0-0.0.0',
    ],
  ],
];

