define(['jquery','https://maps.google.com/maps/api/js?key='+window.apikey+'&'], function ($) {
    // Google Maps API and all its dependencies will be loaded here.
    var GMaps = GMaps || {
        init: function () {
            console.log("Gmaps Init");
            return GMaps;

        }
    };
    return GMaps.init();
    WecMap = WecMap || undefined;

});