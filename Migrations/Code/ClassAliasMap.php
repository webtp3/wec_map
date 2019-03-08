<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    // Flexforms (Backend): neccessary for cal
    'tx_wecmap_backend' => 'JBartels\\WecMap\\Utility\\Backend',

    // Google map service: neccessary for extensions using non-namespaced
    'tx_wecmap_map_google' => 'JBartels\\WecMap\\MapService\\Google\\Map',
    'tx_wecmap_marker_google' => 'JBartels\\WecMap\\MapService\\Google\\Marker',
];
