define(['jquery','https://maps.google.com/maps/api/js?key='+window.apikey], function ($) {
    // Google Maps API and all its dependencies will be loaded here.
    var Gmap = Gmap || {
        init: function () {
            console.log("Gmap Init");
            return Gmap;

        }
    };
    return Gmap.init();
    //WecMap = WecMap || undefined;

});