<?php

namespace Drupal\likebtn\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;


class SettingsProperty extends TypedData {

  /**
   * Settings
   *
   * @var string|null
   */
  protected $settings = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    // if ($definition->getSetting('text source') === NULL) {
    //   throw new \InvalidArgumentException("The definition's 'text source' key has to specify the name of the text property to be processed.");
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->settings !== NULL) {
      return $this->settings;
    }

    $item = $this->getParent();
    
    $likebtn_settings = unserialize(LIKEBTN_SETTINGS);

    $settings = array();
    foreach ($likebtn_settings as $property_name => $options) {
      $settings[$property_name] = $item->{$property_name};
    }

    // echo "<pre>";
    // print_r($settings);
    // exit();
    // foreach ($item as $property_name => $value) {
    //   $settings[$property_name] = $value;
    // }

    $this->settings = 'json_encode($settings)';

    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->settings = $value;
    // Notify the parent of any changes.
    // if ($notify && isset($this->parent)) {
    //   $this->parent->onChange($this->name);
    // }
  }

}
