<?php
/**
 * Created by PhpStorm.
 * User: znak
 * Date: 29.01.17
 * Time: 15:29
 */

namespace Drupal\likebtn\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\likebtn\LikeBtn;
use Drupal\likebtn\LikebtnInterface;
use Drupal\likebtn\LikeBtnMarkup;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\field\Entity\FieldStorageConfig;

class LikeBtnController extends ControllerBase {

  public function nodeLikes($node) {
    $my_entity = Node::load($node);
    return $this->likes($my_entity);
  }

  public function commentLikes($node) {
    $my_entity = Comment::load($node);
    return $this->likes($my_entity);
  }

  public function likes($entity) {
    $rows = $this->likebtn_get_count($entity);
    $total_likes_minus_dislikes = 0;
    foreach ($rows as $row) {
      $total_likes_minus_dislikes += $row['likes_minus_dislikes'];
    }

    $header = array(
      $this->t('Button'),
      $this->t('Likes'),
      $this->t('Dislikes'),
      $this->t('Likes minus dislikes'),
    );

    $result = array(
      '#theme' => 'likebtn_likes_page',
      '#total_likes_minus_dislikes' => $total_likes_minus_dislikes,
      '#header' => $header,
      '#rows' => $rows
    );

    return $result;
  }

  public function likebtnTestSync () {
    $likebtn = new LikeBtn();
    $likebtn_account_email = '';
    $likebtn_account_api_key = '';
    $likebtn_account_site_id = '';

    if (isset($_POST['likebtn_account_email'])) {
      $likebtn_account_email = $_POST['likebtn_account_email'];
    }

    if (isset($_POST['likebtn_account_api_key'])) {
      $likebtn_account_api_key = $_POST['likebtn_account_api_key'];
    }

    if (isset($_POST['likebtn_account_site_id'])) {
      $likebtn_account_site_id = $_POST['likebtn_account_site_id'];
    }

    $test_response = $likebtn->testSync($likebtn_account_email, $likebtn_account_api_key, $likebtn_account_site_id);

    if ($test_response['result'] == 'success') {
      $result_text = t('OK');
    }
    else {
      $result_text = t('Error');
    }

    $response = array(
      'result' => $test_response['result'],
      'result_text' => $result_text,
      'message' => $test_response['message'],
    );

    ob_clean();
    $resp = json_encode($response);
    return new Response($resp);
  }

  private function likebtn_get_count(EntityInterface $entity) {
    $db = \Drupal::database();

    try {
      $query = $db->select('votingapi_vote', 'vv')
        ->fields('vv')
        ->condition('vv.entity_type', $entity->getEntityTypeId())
        ->condition('vv.entity_id', $entity->id())
        ->condition('vv.value_type', 'points')
        //->condition('vv.type', LikebtnInterface::LIKEBTN_VOTING_TAG)
        ->orderBy('vv.vote_source', 'ASC');

      $votingapi_results = $query->execute();
    }
    catch (\Exception $e) {
      return $e;
    }

    // Display a table with like counts per button.
    $rows = array();
    // Like and dislike rows has been found.
    $records_by_source  = array();

    while (1) {
      $record = $votingapi_results->fetchAssoc();

      // Records with likes and dislikes go one after another.
      if (!count($records_by_source) || $record['vote_source'] == $records_by_source[count($records_by_source) - 1]['vote_source']) {
        // Do nothing.
      }
      elseif (count($records_by_source)) {
        $first_record  = $records_by_source[0];
        $second_record = array('value' => 0);
        if (!empty($records_by_source[1])) {
          $second_record = $records_by_source[1];
        }

        if ($first_record['value'] >= 0 && $second_record['value'] <= 0) {
          $likes    = $first_record['value'];
          $dislikes = abs($second_record['value']);
        }
        else {
          $likes    = $second_record['value'];
          $dislikes = abs($first_record['value']);
        }
        $likes_minus_dislikes = $likes - $dislikes;

        $rows[] = array(
          'button' => $this->likebtn_get_name($first_record['vote_source'], $record['entity_type']),
          'likes' => $likes,
          'dislikes' => $dislikes,
          'likes_minus_dislikes' => $likes_minus_dislikes,
        );

        $records_by_source = array();
      }
      $records_by_source[] = $record;

      if (!$record) {
        break;
      }
    }

    return $rows;
  }

  public function likebtn_flatten_field_instance_settings($settings) {
    $flat_settings = array();
    foreach ($settings as $settings_fieldset) {
      if (is_array($settings_fieldset)) {
        foreach ($settings_fieldset as $settings_key => $settings_value) {
          $flat_settings[$settings_key] = $settings_value;
        }
      }
    }
    return $flat_settings;
  }

  public function likebtn_settings_form($default_values = NULL) {
    $likebtn_markup = new LikeBtnMarkup();
    $config = $this->config('likebtn.settings');
    $form = array();

    $likebtn_website_locale = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $likebtn_website_locales = unserialize(LIKEBTN_WEBSITE_LOCALES);
    if (!in_array($likebtn_website_locale, $likebtn_website_locales)) {
      $likebtn_website_locale = 'en';
    }

    $likebtn_settings_lang_options['auto'] = "auto - " . $this->t("Detect from client browser");
    $langs = unserialize(LIKEBTN_LANGS);
    foreach ($langs as $lang_code => $lang_name) {
      $likebtn_settings_lang_options[$lang_code] = $lang_name;
    }

    $likebtn_styles = $config->get('settings.likebtn_styles') ?: array();

    $likebtn_settings_style_options = array();
    if (!$likebtn_styles) {
      $likebtn_styles = unserialize(LIKEBTN_STYLES);
    }
    foreach ($likebtn_styles as $likebtn_style) {
      $likebtn_settings_style_options[$likebtn_style] = $likebtn_style;
    }

    // For assets.
    $public_url = _likebtn_public_url();

    $form['likebtn_settings_item'] = array(
      '#type'          => 'item',
      '#description'   => $this->t('You can find detailed settings description on <a href="@link-likebtn">LikeBtn.com</a>. Options marked with tariff plan name (PLUS, PRO, VIP, ULTRA) are available only if your website is upgraded to corresponding plan (<a href="@link-read_more">read more about plans and pricing</a>).',
        array(
          '@link-likebtn'   => 'http://likebtn.com/en/#settings',
          '@link-read_more' => 'http://likebtn.com/en/#plans_pricing',
        )
      ),
    );

    $form['likebtn_extra_display_options'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Extra display options'),
      '#open'        => FALSE,
    );
    // Settings must be under subelement to be properly flattened for field.
    $form['likebtn_extra_display_options']['likebtn_html_before'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Insert HTML before'),
      '#description'   => $this->t('HTML code to insert before the Like Button'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_html_before']) ?
        $default_values['likebtn_html_before'] : '') : $config->get('settings.likebtn_html_before')),
    );
    $form['likebtn_extra_display_options']['likebtn_html_after'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Insert HTML after'),
      '#description'   => $this->t('HTML code to insert after the Like Button'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_html_after']) ?
        $default_values['likebtn_html_after'] : '') : $config->get('settings.likebtn_html_after')),
    );
    $form['likebtn_extra_display_options']['likebtn_alignment'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Alignment'),
      '#options'       => array(
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right')),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_alignment']) ?
        $default_values['likebtn_alignment'] : 'left') : $config->get('settings.likebtn_alignment')),
    );

    $form['likebtn_settings_style_language'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Style and language'),
      '#weight'      => 4,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_style_language']['likebtn_settings_theme'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Theme'),
      //'#description'   => 'style',
      '#options'       => $likebtn_settings_style_options,
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_theme']) ?
        $default_values['likebtn_settings_theme'] : 'white') : $config->get('settings.likebtn_settings.theme')),
      '#attributes'    => array('class' => array('image_dropdown')),
    );
    $form['likebtn_settings_style_language']['likebtn_settings_lang'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Language'),
      //'#description'   => 'lang',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_lang']) ?
        $default_values['likebtn_settings_lang'] : 'en') : $config->get('settings.likebtn_settings.lang')),
      '#options'       => $likebtn_settings_lang_options,
    );

    $form['likebtn_settings_appearance_behaviour'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Appearance and behaviour'),
      '#weight'      => 5,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_like_enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show Like Button'),
      //'#description'   => 'like_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_like_enabled']) ?
        $default_values['likebtn_settings_like_enabled'] : TRUE) : $config->get('settings.likebtn_settings.like_enabled')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_dislike_enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show Dislike Button'),
      //'#description'   => 'dislike_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_dislike_enabled']) ?
        $default_values['likebtn_settings_dislike_enabled'] : TRUE) : $config->get('settings.likebtn_settings.dislike_enabled')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_show_like_label'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show "like"-label'),
      //'#description'   => 'show_like_label',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_show_like_label']) ?
        $default_values['likebtn_settings_show_like_label'] : TRUE) : $config->get('settings.likebtn_settings.show_like_label')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_show_dislike_label'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show "dislike"-label'),
      //'#description'   => 'show_dislike_label',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_show_dislike_label']) ?
        $default_values['likebtn_settings_show_dislike_label'] : FALSE) : $config->get('settings.likebtn_settings.show_dislike_label')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_icon_like_show'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show like icon'),
      //'#description'   => 'icon_like_show',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_icon_like_show']) ?
        $default_values['likebtn_settings_icon_like_show'] : TRUE) : $config->get('settings.likebtn_settings.icon_like_show')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_icon_dislike_show'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show dislike icon'),
      //'#description'   => 'icon_dislike_show',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_icon_dislike_show']) ?
        $default_values['likebtn_settings_icon_dislike_show'] : TRUE) : $config->get('settings.likebtn_settings.icon_dislike_show')),
    );
    /*$form['likebtn_settings_appearance_behaviour']['likebtn_settings_lazy_load'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Lazy load - if button is outside viewport it is loaded when user scrolls to it'),
      '#description'   => 'lazy_load',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_lazy_load']) ?
        $default_values['likebtn_settings_lazy_load'] : FALSE) : $config->get('settings.likebtn_settings.lazy_load')),
    );*/
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_vert'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Vertical layout'),
      //'#description'   => 'vert',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_vert']) ?
        $default_values['likebtn_settings_vert'] : FALSE) : $config->get('settings.likebtn_settings.vert')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_ef_voting'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Voting animation'),
      //'#description'   => 'ef_voting',
      "#empty_option"  => '',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_ef_voting']) ?
        $default_values['likebtn_settings_ef_voting'] : '') : $config->get('settings.likebtn_settings.ef_voting')),
      '#options'       => array('push'=>'push', 'grow'=>'grow', 'buzz'=>'buzz', 'wobble'=>'wobble', 'bounce'=>'bounce', 'shake'=>'shake', 'heartbeat'=>'heartbeat'),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_white_label'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Remove branding') . ' (VIP, ULTRA)',
      '#description'   => $this->t('No LikeBtn.com branding link in the popup'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_white_label']) ?
        $default_values['likebtn_settings_white_label'] : FALSE) : $config->get('settings.likebtn_settings.white_label')),
      '#states' => array(
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PRO)),
        ),
      ),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_rich_snippet'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Google Rich Snippets'),
      '#description'   => $this->t('<a href="https://likebtn.com/en/faq#rich_snippets" target="_blank">What are Google Rich Snippets and how do they boost traffic?</a>'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_rich_snippet']) ?
        $default_values['likebtn_settings_rich_snippet'] : FALSE) : $config->get('settings.likebtn_settings.rich_snippet')),
    );
    $form['likebtn_settings_appearance_behaviour']['likebtn_settings_event_handler'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('JavaScript callback function serving as an event handler'),
      '#description'   => t('The provided function receives the event object as its single argument. The event object has the following properties: <strong>type</strong> – indicates which event was dispatched ("likebtn.loaded", "likebtn.like", "likebtn.unlike", "likebtn.dislike", "likebtn.undislike"); <strong>settings</strong> – button settings; <strong>wrapper</strong> – button DOM-element'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_event_handler']) ?
        $default_values['likebtn_settings_event_handler'] : NULL) : $config->get('settings.likebtn_settings.event_handler')),
    );
    /*$form['likebtn_settings_appearance_behaviour']['likebtn_settings_info_message'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show information message when the button can not be displayed due to misconfiguration'),
      '#description'   => 'info_message',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_info_message']) ?
        $default_values['likebtn_settings_info_message'] : TRUE) : $config->get('settings.likebtn_settings.info_message')),
    );*/

    $form['likebtn_settings_popup'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Popup'),
      '#weight'      => 5,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_disabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disable popup').' (VIP, ULTRA)',
      //'#description'   => 'popup_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_disabled']) ?
        $default_values['likebtn_settings_popup_disabled'] : FALSE) : $config->get('settings.likebtn_settings.popup_disabled')),
      '#states' => array(
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PRO)),
        ),
      ),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_dislike'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show popup on disliking'),
      //'#description'   => 'popup_dislike',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_dislike']) ?
        $default_values['likebtn_settings_popup_dislike'] : FALSE) : $config->get('settings.likebtn_settings.popup_dislike')),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_on_load'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show popup on button load'),
      //'#description'   => 'popup_on_load',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_on_load']) ?
        $default_values['likebtn_settings_popup_on_load'] : FALSE) : $config->get('settings.likebtn_settings.popup_on_load')),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_position'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Popup position'),
      //'#description'   => 'popup_position',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_position']) ?
        $default_values['likebtn_settings_popup_position'] : TRUE) : $config->get('settings.likebtn_settings.popup_position')),
      '#options'       => array(
        "top"  => $this->t('top'),
        "right" => $this->t('right'),
        "bottom" => $this->t('bottom'),
        "left" => $this->t('left')),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_style'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Popup style'),
      //'#description'   => 'popup_style',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_style']) ?
        $default_values['likebtn_settings_popup_style'] : TRUE) : $config->get('settings.likebtn_settings.popup_style')),
      '#options'       => array(
        "light"  => "light",
        "dark" => "dark"),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_width'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Popup width'),
      //'#description'   => 'popup_width',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_width']) ?
        $default_values['likebtn_settings_popup_width'] : '176') : ($config->get('settings.likebtn_settings.popup_width') ? $config->get('settings.likebtn_settings.popup_width') : '176')),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_hide_on_outside_click'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Hide popup when clicking outside'),
      //'#description'   => 'popup_hide_on_outside_click',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_hide_on_outside_click']) ?
        $default_values['likebtn_settings_popup_hide_on_outside_click'] : TRUE) : $config->get('settings.likebtn_settings.popup_hide_on_outside_click')),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_donate'] = array(
      '#type'          => 'textfield',
      '#id'            => 'popup_donate_input',
      '#title'         => '<img src="' . $public_url . '/assets/img/popup_donate.png" width="16" height="16"/> ' . t('Donate buttons to display in the popup') . ' (VIP, ULTRA)',
      '#maxlength'     => 5000,
      '#description'   => 'popup_donate',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_donate']) ?
        $default_values['likebtn_settings_popup_donate'] : '') : $config->get('settings.likebtn_settings.popup_donate') ?: ''),
      '#states' => array(
        // Enable field.
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_VIP)),
        ),
      ),
    );
    $form['likebtn_settings_popup']['likebtn_configure_buttons'] = array(
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => t('Configure donate buttons'),
      '#allowed_tags' => array('button', 'br'),
      '#attributes' => array(
        'style'=>'position:relative;top:-10px;',
        'onclick'=>'likebtnDG(\'popup_donate_input\');return false;',
      ),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_html'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom HTML to insert into the popup') . ' (PRO, VIP, ULTRA)',
      '#description'   => 'popup_html',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_html']) ?
        $default_values['likebtn_settings_popup_html'] : '') : $config->get('settings.likebtn_settings.popup_html')),
      '#states' => array(
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
        ),
      ),
    );
    $form['likebtn_settings_popup']['likebtn_settings_popup_content_order'] = array(
      '#type'          => 'textfield',
      '#id'            => 'popup_content_order_input',
      '#title'         => $this->t('Order of the content in the popup'),
      '#description'   => 'popup_content_order',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_popup_content_order']) ?
        $default_values['likebtn_settings_popup_content_order'] : 'popup_share,popup_donate,popup_html') : $config->get('settings.likebtn_settings.popup_content_order')),
    );

    $form['likebtn_settings_voting'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Voting'),
      '#weight'      => 5,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_voting']['likebtn_settings_voting_enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Allow voting'),
      //'#description'   => 'voting_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_voting_enabled']) ?
        $default_values['likebtn_settings_voting_enabled'] : TRUE) : $config->get('settings.likebtn_settings.voting_enabled')),
    );
    $form['likebtn_settings_voting']['likebtn_settings_unlike_allowed'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Allow to cancel a vote'),
      //'#description'   => 'unlike_allowed',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_unlike_allowed']) ?
        $default_values['likebtn_settings_unlike_allowed'] : TRUE) : $config->get('settings.likebtn_settings.unlike_allowed')),
    );
    $form['likebtn_settings_voting']['likebtn_settings_voting_both'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Allow to like and dislike at the same time'),
      //'#description'   => 'voting_both',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_voting_both']) ?
        $default_values['likebtn_settings_voting_both'] : FALSE) : $config->get('settings.likebtn_settings.voting_both')),
    );
    $form['likebtn_settings_voting']['likebtn_settings_voting_frequency'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('How often visitor can vote (sec)'),
      //'#description'   => 'voting_frequency',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_voting_frequency']) ?
        $default_values['likebtn_settings_voting_frequency'] : '') : $config->get('settings.likebtn_settings.voting_frequency')),
    );

    $form['likebtn_settings_counter'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Counter'),
      '#weight'      => 6,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_show'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show votes counter'),
      //'#description'   => 'counter_show',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_show']) ?
        $default_values['likebtn_settings_counter_show'] : TRUE) : $config->get('settings.likebtn_settings.counter_show')),
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Counter type'),
      //'#description'   => 'counter_type',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_type']) ?
        $default_values['likebtn_settings_counter_type'] : "number") : $config->get('settings.likebtn_settings.counter_type')),
      '#options'       => array(
        "number"  => $this->t('number'),
        "percent" => $this->t('percent'),
        "subtract_dislikes" => $this->t('subtract_dislikes'),
        "single_number" => $this->t('single_number')),
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_frmt'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Counter format'),
      //'#description'   => 'counter_frmt',
      "#empty_option"  => $this->t('Without thousands separator (3700)'),
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_frmt']) ?
        $default_values['likebtn_settings_counter_frmt'] : "") : $config->get('settings.likebtn_settings.counter_frmt')),
      '#options'       => array(
        "space"  => $this->t('Space as thousands separator (3 700)'),
        "comma"  => $this->t('Comma as thousands separator (3,700)'),
        "period"  => $this->t('Period as thousands separator (3.700)'),
        "apo"  => $this->t("Apostrophe as thousands separator (3'700)"),
        "km"  => $this->t("K for thousands (3.7K), M for millions (15.2M)"),
      )
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_clickable'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Votes counter is clickable'),
      //'#description'   => 'counter_clickable',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_clickable']) ?
        $default_values['likebtn_settings_counter_clickable'] : FALSE) : $config->get('settings.likebtn_settings.counter_clickable')),
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_zero_show'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show zero value in counter'),
      //'#description'   => 'counter_zero_show',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_zero_show']) ?
        $default_values['likebtn_settings_counter_zero_show'] : FALSE) : $config->get('settings.likebtn_settings.counter_zero_show')),
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_count'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Animate number counting'),
      //'#description'   => 'counter_count',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_count']) ?
        $default_values['likebtn_settings_counter_count'] : FALSE) : $config->get('settings.likebtn_settings.counter_count')),
    );
    $form['likebtn_settings_counter']['likebtn_settings_counter_padding'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Counter mask'),
      '#description'   => 'For example set the following mask "0000000" if you need the counter to be displayed as 0000001, 0000002 after receiving first, second and so on votes. This parameter does not set value for buttons. If you need to set number of votes for buttons please do so on Statistics tab.',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_counter_padding']) ?
        $default_values['likebtn_settings_counter_padding'] : NULL) : $config->get('settings.likebtn_settings.counter_padding')),
    );

    $form['likebtn_settings_sharing'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Sharing'),
      '#weight'      => 7,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_sharing']['likebtn_settings_share_enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show share buttons in the popup.') .  ' ' . $this->t('Use popup_enabled option to enable/disable popup.') . ' (PLUS, PRO, VIP, ULTRA)',
      //'#description'   => 'share_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_share_enabled']) ?
        $default_values['likebtn_settings_share_enabled'] : TRUE) : $config->get('settings.likebtn_settings.share_enabled')),
      '#states' => array(
        // Disable field.
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
        ),
      ),
    );
    $form['likebtn_settings_sharing']['likebtn_settings_share_size'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Share buttons size'),
      //'#description'   => 'share_size',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_share_size']) ?
        $default_values['likebtn_settings_share_size'] : "medium") : $config->get('settings.likebtn_settings.share_size')),
      '#options'       => array(
        "small"  => $this->t('Small'),
        ""       => $this->t('Medium'),
        "large"  => $this->t('Large'),
      )
    );
    $form['likebtn_settings_sharing']['likebtn_settings_addthis_service_codes'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('AddThis <a href="@link">service codes</a> separated by comma (max 8). Used to specify which buttons are displayed in share popup. Example: google_plusone_share, facebook, twitter (PRO, VIP, ULTRA)', array(
        '@link' => 'http://www.addthis.com/services/list',
      )),
      //'#description'   => 'addthis_service_codes',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_addthis_service_codes']) ?
        $default_values['likebtn_settings_addthis_service_codes'] : NULL) : $config->get('settings.likebtn_settings.addthis_service_codes')),
      '#states' => array(
        // Disable field.
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
        ),
      ),
    );
    $form['likebtn_settings_sharing']['likebtn_settings_addthis_pubid'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('AddThis <a href="@link-profile-id">Profile ID</a>. Allows to collect sharing statistics and view it on AddThis <a href="@link-analytics-page">analytics page</a> (PRO, VIP, ULTRA)',
        array(
          '@link-profile-id'     => 'https://www.addthis.com/settings/publisher',
          '@link-analytics-page' => 'http://www.addthis.com/analytics',
        )
      ),
      //'#description'   => 'addthis_pubid',
      '#maxlength'     => 30,
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_addthis_pubid']) ?
        $default_values['likebtn_settings_addthis_pubid'] : NULL) : $config->get('settings.likebtn_settings.addthis_pubid')),
      '#states' => array(
        // Disable field.
        'disabled' => array(
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_FREE)),
          array(':input[name="likebtn_plan"]' => array('value' => LikebtnInterface::LIKEBTN_PLAN_PLUS)),
        ),
      ),
    );

    /*$form['likebtn_settings_loader'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Loader'),
      '#weight'      => 8,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_loader']['likebtn_settings_loader_show'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show loader while button is loading'),
      '#description'   => 'loader_show',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_loader_show']) ?
        $default_values['likebtn_settings_loader_show'] : FALSE) : $config->get('settings.likebtn_settings.loader_show')),
    );
    $form['likebtn_settings_loader']['likebtn_settings_loader_image'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Loader image URL (if empty, default image is used)'),
      '#description'   => 'loader_image',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_loader_image']) ?
        $default_values['likebtn_settings_loader_image'] : NULL) : $config->get('settings.likebtn_settings.loader_image')),
    );*/

    $form['likebtn_settings_tooltips'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Tooltips'),
      '#weight'      => 9,
      '#open'        => FALSE
    );
    $form['likebtn_settings_tooltips']['likebtn_settings_tooltip_enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show tooltips'),
      //'#description'   => 'tooltip_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_tooltip_enabled']) ?
        $default_values['likebtn_settings_tooltip_enabled'] : TRUE) : $config->get('settings.likebtn_settings.tooltip_enabled')),
    );
    $form['likebtn_settings_tooltips']['likebtn_settings_tooltip_like_show_always'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Always show Like button tooltip'),
      //'#description'   => 'tooltip_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_tooltip_like_show_always']) ?
        $default_values['likebtn_settings_tooltip_like_show_always'] : FALSE) : $config->get('settings.likebtn_settings.tooltip_like_show_always')),
    );
    $form['likebtn_settings_tooltips']['likebtn_settings_tooltip_dislike_show_always'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Always show Dislike button tooltip'),
      //'#description'   => 'tooltip_enabled',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_tooltip_dislike_show_always']) ?
        $default_values['likebtn_settings_tooltip_dislike_show_always'] : FALSE) : $config->get('settings.likebtn_settings.tooltip_dislike_show_always')),
    );

    $form['likebtn_settings_i18n'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Texts'),
      '#weight'      => 11,
      '#open'        => FALSE,
    );
    $form['likebtn_settings_i18n']['likebtn_settings_rtl'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Right-to-left (RTL)'),
      //'#description'   => 'rtl',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_rtl']) ?
        $default_values['likebtn_settings_rtl'] : FALSE) : $config->get('settings.likebtn_settings.rtl')),
    );
    $form['likebtn_settings_i18n']['likebtn_translate'] = array(
      '#type'          => 'item',
      '#description'   => $this->t('<a href="https://likebtn.com/en/translate-like-button-widget" target="_blank">Send us translation</a>'),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_like'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Like button text'),
      //'#description'   => 'i18n_like',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_like']) ?
        $default_values['likebtn_settings_i18n_like'] : NULL) : $config->get('settings.likebtn_settings.i18n_like')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_dislike'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Dislike button text'),
      //'#description'   => 'i18n_dislike',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_dislike']) ?
        $default_values['likebtn_settings_i18n_dislike'] : NULL) : $config->get('settings.likebtn_settings.i18n_dislike')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_after_like'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Like button text after liking'),
      //'#description'   => 'i18n_after_like',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_after_like']) ?
        $default_values['likebtn_settings_i18n_after_like'] : NULL) : $config->get('settings.likebtn_settings.i18n_after_like')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_after_dislike'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Dislike button text after disliking'),
      //'#description'   => 'i18n_after_dislike',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_after_dislike']) ?
        $default_values['likebtn_settings_i18n_after_dislike'] : NULL) : $config->get('settings.likebtn_settings.i18n_after_dislike')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_like_tooltip'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Like button tooltip'),
      //'#description'   => 'i18n_like_tooltip',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_like_tooltip']) ?
        $default_values['likebtn_settings_i18n_like_tooltip'] : NULL) : $config->get('settings.likebtn_settings.i18n_like_tooltip')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_dislike_tooltip'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Dislike button tooltip'),
      //'#description'   => 'i18n_dislike_tooltip',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_dislike_tooltip']) ?
        $default_values['likebtn_settings_i18n_dislike_tooltip'] : NULL) : $config->get('settings.likebtn_settings.i18n_dislike_tooltip')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_unlike_tooltip'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Like button tooltip after liking'),
      //'#description'   => 'i18n_unlike_tooltip',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_unlike_tooltip']) ?
        $default_values['likebtn_settings_i18n_unlike_tooltip'] : NULL) : $config->get('settings.likebtn_settings.i18n_unlike_tooltip')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_undislike_tooltip'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Dislike button tooltip after disliking'),
      //'#description'   => 'i18n_undislike_tooltip',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_undislike_tooltip']) ?
        $default_values['likebtn_settings_i18n_undislike_tooltip'] : NULL) : $config->get('settings.likebtn_settings.i18n_undislike_tooltip')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_share_text'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Text before share buttons'),
      //'#description'   => 'i18n_share_text',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_share_text']) ?
        $default_values['likebtn_settings_i18n_share_text'] : NULL) : $config->get('settings.likebtn_settings.i18n_share_text')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_popup_close'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Popup close button text'),
      //'#description'   => 'i18n_popup_close',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_popup_close']) ?
        $default_values['likebtn_settings_i18n_popup_close'] : NULL) : $config->get('settings.likebtn_settings.i18n_popup_close')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_popup_text'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Popup text when sharing disabled'),
      //'#description'   => 'i18n_popup_text',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_popup_text']) ?
        $default_values['likebtn_settings_i18n_popup_text'] : NULL) : $config->get('settings.likebtn_settings.i18n_popup_text')),
    );
    $form['likebtn_settings_i18n']['likebtn_settings_i18n_popup_donate'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Text before donate buttons'),
      //'#description'   => 'i18n_popup_donate',
      '#default_value' => ($default_values ? (isset($default_values['likebtn_settings_i18n_popup_donate']) ?
        $default_values['likebtn_settings_i18n_popup_donate'] : NULL) : $config->get('settings.likebtn_settings.i18n_popup_donate')),
    );

    $form['likebtn_demo_fieldset'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Demo'),
      '#weight'      => 12,
      '#open'        => TRUE
    );

    $form['likebtn_demo_fieldset']['likebtn_demo'] = $likebtn_markup->likebtn_get_markup('live_demo', 1, $default_values);

    $form['#attached']['library'][] = 'likebtn/likebtn-libraries';

    return $form;
  }

  public function likebtn_field_load($field, $item, $instance) {
    $field_info = likebtn_field_info();
    $keys = array_keys($field_info['likebtn_field']['settings']);
    $value = array();

    foreach ($keys as $key) {
      if (isset($item[$key])) {
        $value[$key] = $item[$key];
      }
      else {
        // Search for key in instance settings.
        // We have to come through instance settings as it is 2-dimentional.
        // array due to form fieldsets.
        $instance_settings_exists = FALSE;
        foreach ($instance['settings'] as $instance_settings) {
          if (is_array($instance_settings)) {
            foreach ($instance_settings as $instance_settings_key => $instance_settings_value) {
              if ($instance_settings_key == $key) {
                $instance_settings_exists = TRUE;
                break;
              }
              if ($instance_settings_exists) {
                $value[$key] = $instance_settings_value;
              }
              else {
                // New option has not been activated.
                if (isset($field['settings'][$key])) {
                  $value[$key] = $field['settings'][$key];
                }
                else {
                  $settings = unserialize(LIKEBTN_SETTINGS);
                  $value[$key] = $settings[$key]['default'];
                }
              }
            }
          }
        }

      }
    }
    return $value;
  }

  public function likebtn_get_name($source, $entity_type) {

    $source = preg_replace("/^likebtn_/", '', $source);

    $name = $source;

    $source_parts = explode('_', $source);

    if (empty($source_parts[2]) || $source_parts[2] != 'field') {
      $name = t('Like Button');
    }
    else {
      list($entity_type, $entity_id, $field_id, $field_index) = likebtn_parse_field_identifier($source);

      // Get field name.
      if (!empty($field_id)) {

        //$field_info = field_info_field_by_id($source_parts[1]);
        //$field_config = \Drupal::config('field.storage.node.field_like_button_1')->get();
        $field_config = FieldStorageConfig::loadByName($entity_type, 'field_'.$field_id);

        if ($field_config) {

          $name = $this->t('Field') . ': ' . $field_id;

          if ($name && !empty($field_index)) {
            $name .= ' (' . ($field_index+1) . ')';
          }
        }
      }
    }

    return $name;
  }

  public function likebtn_prepare_option($option_name, $option_value) {
    $settings = unserialize(LIKEBTN_SETTINGS);

    $option_value_prepared = $option_value;

    if (isset($settings[$option_name]) && is_bool($settings[$option_name]['default'])) {
      if (is_int($option_value) || is_string($option_value) || is_bool($option_value)) {
        if ($option_value) {
          $option_value_prepared = 'true';
        }
        else {
          $option_value_prepared = 'false';
        }
      }
    }

    // To avoid XSS.
    $option_value_prepared = htmlspecialchars($option_value_prepared);

    return $option_value_prepared;
  }
}
