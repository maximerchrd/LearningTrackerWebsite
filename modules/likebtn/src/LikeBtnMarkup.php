<?php

namespace Drupal\likebtn;

use Drupal\Core\Entity\Entity;
use Drupal\likebtn\Controller\LikeBtnController;

class LikeBtnMarkup {

  public function likebtn_get_markup($element_name, $element_id, $values = NULL, $wrap = TRUE, $use_entity_settings = TRUE) {
    $prepared_settings = array();
    $config = \Drupal::config('likebtn.settings');
    $controller = new LikeBtnController();

    $likebtn = new LikeBtn();
    $likebtn->runSyncVotes();

    $settings = unserialize(LIKEBTN_SETTINGS);

    $data = '';
    if ($element_name && $element_id) {
      $data .= 'data-identifier="' . $element_name . '_' . $element_id . '"';
    }

    $site_id = $config->get('general.likebtn_account_data_site_id');
    if ($site_id) {
      $data .= ' data-site_id="' . $site_id . '" ';
    }

    // Website subdirectory.
    if ($config->get('settings.likebtn_settings_subdirectory')) {
      $data .= ' data-subdirectory="' . $config->get('settings.likebtn_settings.subdirectory') . '" ';
    }

    $data .= ' data-engine="drupal" data-engine_v="' .\Drupal::VERSION . '"';
    $data .= ' data-plugin_v="' . LikebtnInterface::LIKEBTN_VERSION . '" ';

    foreach ($settings as $option_name => $option_info) {
      $option_value = '';
      if ($values) {
        if (isset($values['settings.likebtn_settings.' . $option_name])) {
          $option_value = $values['settings.likebtn_settings.' . $option_name];
        } else if (isset($values['likebtn_' . $option_name])) {
          $option_value = $values['likebtn_' . $option_name];
        } else if (isset($values['likebtn_settings_' . $option_name])) {
          $option_value = $values['likebtn_settings_' . $option_name];
        } elseif (isset($values[$option_name])) {
          $option_value = $values[$option_name];
        }
        else {
          //$option_value = '';
          continue;
        }
      }
      elseif ($use_entity_settings) {
        $option_value = $config->get('settings.likebtn_settings.' . $option_name) ?: '';
      }

      $option_value_prepared = $controller->likebtn_prepare_option($option_name, $option_value);
      $prepared_settings[$option_name] = $option_value_prepared;

      // Do not add option if it has default value.
      if (!is_array($option_value) && /*$option_value_prepared !== '' &&*/ $option_value_prepared != $controller->likebtn_prepare_option($option_name, $settings[$option_name]['default'])) {
        $data .= ' data-' . $option_name . '="' . $option_value_prepared . '" ';
      }
    }

    // Add item options.
    //if ($include_entity_data) {
    if (empty($prepared_settings['item_url']) || empty($prepared_settings['item_title'])) {
      $entity_list = array();
      $entity = NULL;
      $entity_url = '';
      $entity_title = '';
      $entity_date = '';

      if (!empty($entity_list)) {
        $entity = array_shift($entity_list);
      }
      if ($entity && (isset($entity->title) || isset($entity->subject))) {
        // URL.
        if (empty($prepared_settings['item_url'])) {
          $entity_url_object = Entity::uri($element_name, $entity);

          if (!empty($entity_url_object['path'])) {
            global $base_url;
            $entity_url = $base_url . '/' . $entity_url_object['path'];
          }
        }

        // Title.
        if (empty($prepared_settings['item_title'])) {
          if (isset($entity->title)) {
            $entity_title = $entity->title;
          }
          elseif (isset($entity->subject)) {
            $entity_title = $entity->subject;
          }
        }

        // Date.
        if (empty($prepared_settings['item_date'])) {
          if (isset($entity->created)) {
            $entity_date = date("c", $entity->created);
          }
        }
      }

      if ($entity_url) {
        $data .= ' data-item_url="' . $entity_url . '" ';
      }
      if ($entity_title) {
        $entity_title = htmlspecialchars($entity_title);
        $data .= ' data-item_title="' . $entity_title . '" ';
      }
      if ($entity_date) {
        $data .= ' data-item_date="' . $entity_date . '" ';
      }
    }
    //}

    $public_url = _likebtn_public_url();

    $html_before = '';
    if (isset($values['likebtn_html_before'])) {
      $html_before = $values['likebtn_html_before'];
    }
    elseif ($use_entity_settings) {
      $html_before = $config->get('settings.likebtn_html_before');
    }

    $html_after = '';
    if (isset($values['likebtn_html_after'])) {
      $html_after = $values['likebtn_html_after'];
    }
    elseif ($use_entity_settings) {
      $html_after = $config->get('settings.likebtn_html_after');
    }

    $alignment = '';
    if ($wrap) {
      if (isset($values['likebtn_alignment'])) {
        $alignment = $values['likebtn_alignment'];
      }
      elseif ($use_entity_settings) {
        $alignment = $config->get('settings.likebtn_alignment');
      }
    }

    return array(
      '#theme' => 'likebtn_markup',
      '#data' => $data,
      '#aligment' => $alignment,
      '#html_before' => $html_before,
      '#html_after' => $html_after
    );
  }

  public function likebtn_render_markup($element_name, $element_id, $values = NULL, $wrap = TRUE, $use_entity_settings = TRUE)
  {
    $twig_service = \Drupal::service('twig');

    $variables = $this->likebtn_get_markup($element_name, $element_id, $values, $wrap, $use_entity_settings);

    foreach($variables as $key => $value) {
      $new_key = preg_replace("/^#/", '', $key);
      $variables[$new_key] = $value;
      unset($variables[$key]);
    }

    return $twig_service->loadTemplate('likebtn-markup.html.twig')->render($variables);
  }
}
