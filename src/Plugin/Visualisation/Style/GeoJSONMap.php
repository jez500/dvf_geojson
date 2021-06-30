<?php

namespace Drupal\dvf_geojson\Plugin\Visualisation\Style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dvf\Plugin\Visualisation\Style\VisualisationStyleBase;

/**
 * Plugin implementation of the 'dvf_geojson_map' visualisation style.
 *
 * @VisualisationStyle(
 *   id = "dvf_geojson_map",
 *   label = @Translation("GeoJSON Map")
 * )
 */
class GeoJSONMap extends VisualisationStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'map' => [
          'center' => [
            'lat' => '40.67878',
            'lng' => '-73.94408',
          ],
          'zoom' => 10,
          'height' => 400,
        ],
      ] + parent::defaultConfiguration();
  }

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['map'] = [
      '#type' => 'details',
      '#title' => $this->t('Map settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['map']['center'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map default center'),
      '#tree' => TRUE,
    ];

    $form['map']['center']['lat'] = [
      '#type' => 'textfield',
      '#title' => 'Latitude',
      '#default_value' => $this->config('map', 'center', 'lat'),
    ];

    $form['map']['center']['lng'] = [
      '#type' => 'textfield',
      '#title' => 'Longitude',
      '#default_value' => $this->config('map', 'center', 'lng'),
    ];

    $form['map']['zoom'] = [
      '#type' => 'number',
      '#title' => 'Map zoom',
      '#max' => 20,
      '#min' => 1,
      '#default_value' => $this->config('map', 'zoom'),
    ];

    $form['map']['height'] = [
      '#type' => 'number',
      '#title' => 'Map height',
      '#default_value' => $this->config('map', 'height'),
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function build() {
    $map_id = hash('sha256', time() . mt_rand());

    $map = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dvf--wrapper', 'dvf-maps--wrapper']],
    ];

    $map['map'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['data-dvfgeojson' => $map_id, 'id' => $map_id],
    ];

    $settings = $this->config('map');
    $settings['geojson_url'] = $this->getDatasetDownloadUri();
    $settings['geojson'] = $this->getVisualisation()->getSourcePlugin()->getRecords();

    $map['#attached']['library'] = ['dvf_geojson/dvfGeoJSON'];
    $map['#attached']['drupalSettings']['dvf']['geojson'][$map_id] = $settings;

    return $map;
  }

}
