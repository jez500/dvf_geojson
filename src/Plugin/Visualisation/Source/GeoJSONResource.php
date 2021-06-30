<?php

namespace Drupal\dvf_geojson\Plugin\Visualisation\Source;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dvf\DvfHelpers;
use Drupal\dvf\Plugin\VisualisationInterface;
use Drupal\dvf\Plugin\Visualisation\Source\VisualisationSourceBase;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'dvf_geojson_resource' visualisation source.
 *
 * @VisualisationSource(
 *   id = "dvf_geojson_resource",
 *   label = @Translation("GeoJSON resource"),
 *   category = @Translation("Maps"),
 *   visualisation_types = {
 *     "dvf_file",
 *     "dvf_url"
 *   }
 * )
 */
class GeoJSONResource extends VisualisationSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * DVF Helpers.
   *
   * @var \Drupal\dvf\DvfHelpers
   */
  protected $dvfHelpers;

  /**
   * Constructs a new CkanResource.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\dvf\Plugin\VisualisationInterface $visualisation
   *   The visualisation context in which the plugin will run.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   Instance of the logger object.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\dvf\DvfHelpers $dvf_helpers
   *   The DVF helpers.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    VisualisationInterface $visualisation = NULL,
    ModuleHandlerInterface $module_handler,
    LoggerInterface $logger,
    Client $http_client,
    DvfHelpers $dvf_helpers,
    CacheBackendInterface $cache
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualisation, $module_handler, $logger, $http_client);
    $this->dvfHelpers = $dvf_helpers;
    $this->cache = $cache;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\dvf\Plugin\VisualisationInterface $visualisation
   *   The visualisation context in which the plugin will run.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    VisualisationInterface $visualisation = NULL
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $visualisation,
      $container->get('module_handler'),
      $container->get('logger.channel.dvf'),
      $container->get('http_client'),
      $container->get('dvf.helpers'),
      $container->get('cache.dvf_csv')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRecords() {
    $cache_key = $this->getCacheKey();
    $cache_object = $this->cache->get($cache_key);

    if (is_object($cache_object)) {
      $data = $cache_object->data;
    }
    else {
      $data = $this->fetchData();
      $this->cache->set($cache_key, $data, $this->getCacheExpiry());
    }

    return $data;
  }


  /**
   * Fetches the JSON data.
   *
   * @return array
   *   Associative array response data.
   */
  protected function fetchData() {
    try {
      $uri = $this->config('uri');
      $response = $this->getContentFromUri($uri);
    }
    catch (\Exception $e) {
      $this->messenger()->addError('Unable to read GeoJSON');
      $this->logger->error($this->t('Error reading GeoJSON file :error',
        [':error' => $e->getMessage()]));
      $response = NULL;
    }

    if (!$response || !$this->dvfHelpers->validateJson($response)) {
      $this->messenger()->addError('Invalid GeoJSON file provided');
      $this->logger->error($this->t('Unable to parse this GeoJSON file :url',
        [':url' => $uri]));
      return [];
    }

    return json_decode($response, TRUE);
  }

  /**
   * Gets a cache key for this plugin.
   *
   * @return string
   *   The cache key.
   */
  protected function getCacheKey() {
    $plugin_id = hash('sha256', $this->getPluginId());
    $uri = $this->config('uri');

    return $plugin_id . ':' . $uri;
  }

}
