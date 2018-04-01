<?php

namespace Drupal\likebtn\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
 
/**
 * @FieldType(
 *   id = "likebtn_field",
 *   label = @Translation("Like Button"),
 *   default_formatter = "likebtn_formatter",
 *   default_widget = "likebtn_widget"
 * )
 */
 
class LikebtnField extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['likebtn_likes'] = DataDefinition::create('integer');
    $properties['likebtn_dislikes'] = DataDefinition::create('integer');
    $properties['likebtn_likes_minus_dislikes'] = DataDefinition::create('integer');
      //->setLabel(t('Settings'));
      //->setClass('\Drupal\likebtn\Plugin\Field\FieldType\Setting sProperty');
      //->setRequired(TRUE);

    // $properties['lang'] = DataDefinition::create('string');

    // $settings = unserialize(LIKEBTN_SETTINGS);

    // foreach ($settings as $key => $options) {
    //   $properties[$key] = DataDefinition::create('string');
    // }

    return $properties;
  }
 
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    // return [
    //   'columns' => [
    //     'settings' => [
    //       'type' => 'varchar',
    //       'length' => 255,
    //     ],
    //   ],
    // ];

    $columns = array();
    $columns['likebtn_likes'] = array(
      'type' => 'int',
      'unsigned' => TRUE,
      'size' => 'big',
    );
    $columns['likebtn_dislikes'] = array(
      'type' => 'int',
      'unsigned' => TRUE,
      'size' => 'big',
    );
    $columns['likebtn_likes_minus_dislikes'] = array(
      'type' => 'int',
      'unsigned' => FALSE,
      'size' => 'big',
    );
    // $settings = unserialize(LIKEBTN_SETTINGS);

    // foreach ($settings as $key => $options) {
    //   $columns[$key] = array(
    //     'type' => 'varchar',
    //     'length' => 255,
    //   );
    // }
    // $arr = [
    //   'columns' => [
    //     'settings' => [
    //       'type' => 'varchar',
    //       'length' => 255,
    //     ],
    //   ],
    // ];
    $result = array('columns' => $columns);
// echo "<pre>";
// print_r($result);
// exit();
    return $result;
    // return [
    //   'columns' => [
    //     'settings' => [
    //       'type' => 'varchar',
    //       'length' => 255,
    //     ],
    //   ],
    // ];
    // $columns = array(
    //   'settings' => array(
    //     'description' => 'Settings',
    //     'type' => 'string',
    //   ),
    // );
   
    // $schema = array(
    //   'columns' => $columns,
    //   'indexes' => array(),
    //   'foreign keys' => array(),
    // );

    // return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
    //return $this->settings === NULL || $this->settings === '';
  }

  public function getConfig()
  {
    return $this->definition->getFieldDefinition()->getDefaultValueLiteral();
  }

  /**
   * {@inheritdoc}
   */
  /*public function isEmpty() {
    $settings = $this->get('settings')->getValue();
    return $settings === NULL || $settings === '';
  }*/

  /**
   * {@inheritdoc}
   */
  /*public static function defaultFieldSettings() {
    return [
      // Declare a single setting, 'size', with a default
      // value of 'large'
      'settings' => '',
    ] + parent::defaultFieldSettings();
  }*/

  /**
   * {@inheritdoc}
   */
  /*public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    
    // $controller = new LikeBtnController();
    // $form = $controller->likebtn_settings_form();

    // return $form;

    $element = [];
    $element['settings'] = array(
      '#type' => 'textfield',
      '#title' => t('Settings'),
      '#description' => t('Select a password for the user'),
    );

    // // The key of the element should be the setting name
    // $element['size'] = [
    //   '#title' => $this->t('Size'),
    //   '#type' => 'select',
    //   '#options' => [
    //     'small' => $this->t('Small'),
    //     'medium' => $this->t('Medium'),
    //     'large' => $this->t('Large'),
    //   ],
    //   '#default_value' => $this->getSetting('size'),
    // ];

    return $element;
  }*/

  /**
   * {@inheritdoc}
   */
  // public function preSave() {
  //   echo 123;
  //   exit();
  // }
}
