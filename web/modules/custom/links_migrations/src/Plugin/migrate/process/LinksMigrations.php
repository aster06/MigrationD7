<?php

namespace Drupal\links_migrations\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a process plugin to replace old links with new ones.
 *
 * @MigrateProcessPlugin(
 *   id = "links_migration"
 * )
 */
class LinksMigrations extends ProcessPluginBase implements ContainerFactoryPluginInterface{

  /**
   * Constructs a new LinksMigrations object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param PathValidatorInterface $path_validator
   *    The path validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected PathValidatorInterface $path_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.validator')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    $dom = Html::load($value);
    foreach ($dom->getElementsByTagName('a') as $element) {
      $link = $element->getAttribute('href');
      // If the 'href' attribute is empty or starts with '#', skip to the next iteration.
      if (!$link || $link[0] === '#') {
        continue;
      }
      // Replace old link with the new link.
      $new_link = str_replace('https://ehistory.osu.edu/', '', $link);
      if ($new_link === $link) {
        continue;
      }
      // Check if the new link is a valid internal path.
      if ($url = $this->path_validator->getUrlIfValidWithoutAccessCheck($new_link)) {
        $generated_url = $url->toString();
        $element->setAttribute('href', $generated_url);
      }

    }
    return Html::serialize($dom);
  }

}

