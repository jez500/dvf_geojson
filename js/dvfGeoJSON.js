;(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the dvfGeoJSON behavior to visualisations.
   */
  Drupal.behaviors.dvfGeoJSON = {
    attach: function (context) {
      $('[data-dvfgeojson]', context).once('dvf-geojson').each(function () {
        // Get DVF instance.
        var $map = $(this),
          mapId = $map.data('dvfgeojson'),
          settings = drupalSettings.dvf.geojson[mapId];

        // Set height.
        $map.height(parseInt(settings.height));

        // New map with center and zoom.
        var DVFMap = L.map(mapId).setView([settings.center.lat, settings.center.lng], parseInt(settings.zoom));

        // Add tile layer.
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(DVFMap);

        // Add the GeoJSON layer.
        L.geoJSON(settings.geojson).addTo(DVFMap);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
