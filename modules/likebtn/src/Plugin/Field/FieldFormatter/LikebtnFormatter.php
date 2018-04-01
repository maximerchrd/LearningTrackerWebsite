<?php
 
namespace Drupal\likebtn\Plugin\Field\FieldFormatter;
 
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\likebtn\LikeBtnMarkup;

/**
 * @FieldFormatter(
 *   id = "likebtn_formatter",
 *   label = @Translation("Like Button Formatter"),
 *   field_types = {
 *     "likebtn_field",
 *   }
 * )
 */
 
class LikebtnFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $likebtn_markup = new LikeBtnMarkup();
    $default_values = array();
    
    // foreach ($items as $delta => $item) {
    //   $markup = '';
    //   $configs = $item->getConfig();
    //   foreach ($configs as $i => $config) {
    //     $default_values = likebtn_default_values_from_config($config);
    //     $markup .= $likebtn_markup->likebtn_get_markup('live_demo', 1, $default_values);
    //   }
      
    //   // The text value has no text format assigned to it, so the user input
    //   // should equal the output, including newlines.
    //   $elements[$delta] = [
    //     '#type' => 'inline_template',
    //     '#template' => $markup
    //     //'#context' => ['settings' => '123'],
    //   ];
    // }
    // if (empty($elements)) {
    $definition = $items->getFieldDefinition();
    $entity_type = $definition->getTargetEntityTypeId();
    $entity_values = $items->getEntity()->toArray();
    $entity_id = (int)$entity_values['nid'][0]['value'];
    $field_id = preg_replace("/^field_/", '', $items->getName());

    $configs = $items->getFieldDefinition()->getDefaultValueLiteral();
    foreach ($configs as $delta => $config) {

      //$entity->$entity_id_key . '_field_' . $instance['field_id'] . '_index_' . $delta

      $default_values = likebtn_default_values_from_config($config);
      $markup = $likebtn_markup->likebtn_render_markup($entity_type, $entity_id . '_field_' . $field_id . '_index_' . $delta, $default_values);

      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => $markup
        //'#context' => ['settings' => '123'],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  /*public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => '123',
        '#url' => 'https://test.com',
      ];
    }
    // foreach ($items as $delta => $item) {
    //   $elements[$delta] = array(
    //     //'uid' => array(
    //       '#markup' =>'123',
    //     //),
    //     // Add more content
    //   );
    // }
 
    return $elements;
  }*/
}