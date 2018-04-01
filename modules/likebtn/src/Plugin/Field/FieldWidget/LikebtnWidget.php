<?php

namespace Drupal\likebtn\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\likebtn\Controller\LikeBtnController;
 
/**
 * @FieldWidget(
 *   id = "likebtn_widget",
 *   label = @Translation("Like Button Widget"),
 *   field_types = {
 *     "likebtn_field",
 *   }
 * )
 */
 
class LikebtnWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Do not show inputs when editing content
    if ($form['#parents'] != array('default_value_input')) {
      return $element;
    }

    // $submit_handlers = $form_state->getSubmitHandlers();
    // $submit_handlers[] = 'likebtn_field_submit_handler';
    // $form_state->setSubmitHandlers($submit_handlers);

    //$settings = unserialize(LIKEBTN_SETTINGS);
    $default_values = array('dummy');
    // foreach ($items[$delta] as $key => $value) {
    //   $default_values[$key] = $value;
    // }

    $configs = $items[$delta]->getConfig();
    
    if (empty($configs)) {
      $default_values = array('dummy');
    } else {
      if (!empty($configs[$delta])) {
        $config = $configs[$delta];
      } else {
        $config = $configs[0];
      }
      $default_values = likebtn_default_values_from_config($config);
    }

// echo "<pre>";
// print_r($items[$delta]);
// exit();
    //file_put_contents("/home/dr8_2e286y/tmp/items.txt", serialize($items[$delta]));
  
    $controller = new LikeBtnController();
    $settings_form = $controller->likebtn_settings_form($default_values);
   
    $element = $element + $settings_form;

    /*$element['settings'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->settings) ? $items[$delta]->settings : NULL,
      // '#placeholder' => $this->getSetting('placeholder'),
      // '#size' => $this->getSetting('size'),
      // '#maxlength' => Email::EMAIL_MAX_LENGTH,
    ];*/
    return $element;
  }
}