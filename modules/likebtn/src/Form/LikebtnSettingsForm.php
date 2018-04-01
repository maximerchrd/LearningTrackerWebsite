<?php
/**
 * Created by PhpStorm.
 * User: znak
 * Date: 08.01.17
 * Time: 17:17
 */

namespace Drupal\likebtn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\likebtn\Controller\LikeBtnController;

class LikebtnSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'likebtn.settings'
    ];
  }

  public function getFormId() {
    return 'likebtn.settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $controller = new LikeBtnController();
    $form = $controller->likebtn_settings_form();

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('likebtn.settings');

    $config
      ->set('settings.likebtn_alignment', $values['likebtn_alignment'])
      ->set('settings.likebtn_html_before', $values['likebtn_html_before'])
      ->set('settings.likebtn_html_after', $values['likebtn_html_after'])

      ->set('settings.likebtn_settings.theme', $values['likebtn_settings_theme'])
      ->set('settings.likebtn_settings.lang', $values['likebtn_settings_lang'])
      ->set('settings.likebtn_settings.show_like_label', $values['likebtn_settings_show_like_label'])
      ->set('settings.likebtn_settings.show_dislike_label', $values['likebtn_settings_show_dislike_label'])
      ->set('settings.likebtn_settings.popup_dislike', $values['likebtn_settings_popup_dislike'])
      ->set('settings.likebtn_settings.like_enabled', $values['likebtn_settings_like_enabled'])
      ->set('settings.likebtn_settings.icon_like_show', $values['likebtn_settings_icon_like_show'])
      ->set('settings.likebtn_settings.icon_dislike_show', $values['likebtn_settings_icon_dislike_show'])
      //->set('settings.likebtn_settings.lazy_load', $values['likebtn_settings_lazy_load'])
      ->set('settings.likebtn_settings.dislike_enabled', $values['likebtn_settings_dislike_enabled'])
      ->set('settings.likebtn_settings.voting_enabled', $values['likebtn_settings_voting_enabled'])
      ->set('settings.likebtn_settings.unlike_allowed', $values['likebtn_settings_unlike_allowed'])
      ->set('settings.likebtn_settings.voting_both', $values['likebtn_settings_voting_both'])
      ->set('settings.likebtn_settings.white_label', $values['likebtn_settings_white_label'])
      ->set('settings.likebtn_settings.rich_snippet', $values['likebtn_settings_rich_snippet'])
      ->set('settings.likebtn_settings.popup_disabled', $values['likebtn_settings_popup_disabled'])
      ->set('settings.likebtn_settings.popup_position', $values['likebtn_settings_popup_position'])
      ->set('settings.likebtn_settings.popup_style', $values['likebtn_settings_popup_style'])
      ->set('settings.likebtn_settings.popup_hide_on_outside_click', $values['likebtn_settings_popup_hide_on_outside_click'])
      ->set('settings.likebtn_settings.popup_donate', $values['likebtn_settings_popup_donate'])
      ->set('settings.likebtn_settings.popup_html', $values['likebtn_settings_popup_html'])
      ->set('settings.likebtn_settings.event_handler', $values['likebtn_settings_event_handler'])
      //->set('settings.likebtn_settings.info_message', $values['likebtn_settings_info_message'])
      ->set('settings.likebtn_settings.counter_type', $values['likebtn_settings_counter_type'])
      ->set('settings.likebtn_settings.counter_clickable', $values['likebtn_settings_counter_clickable'])
      ->set('settings.likebtn_settings.counter_show', $values['likebtn_settings_counter_show'])
      ->set('settings.likebtn_settings.counter_padding', $values['likebtn_settings_counter_padding'])
      ->set('settings.likebtn_settings.counter_zero_show', $values['likebtn_settings_counter_zero_show'])
      ->set('settings.likebtn_settings.share_enabled', $values['likebtn_settings_share_enabled'])
      ->set('settings.likebtn_settings.addthis_pubid', $values['likebtn_settings_addthis_pubid'])
      ->set('settings.likebtn_settings.addthis_service_codes', $values['likebtn_settings_addthis_service_codes'])
      ->set('settings.likebtn_settings.voting_frequency', $values['likebtn_settings_voting_frequency'])
      //->set('settings.likebtn_settings.loader_show', $values['likebtn_settings_loader_show'])
      //->set('settings.likebtn_settings.loader_image', $values['likebtn_settings_loader_image'])
      ->set('settings.likebtn_settings.tooltip_enabled', $values['likebtn_settings_tooltip_enabled'])
      ->set('settings.likebtn_settings.vert', $values['likebtn_settings_vert'])
      ->set('settings.likebtn_settings.ef_voting', $values['likebtn_settings_ef_voting'])
      ->set('settings.likebtn_settings.counter_count', $values['likebtn_settings_counter_count'])
      ->set('settings.likebtn_settings.rtl', $values['likebtn_settings_rtl'])
      ->set('settings.likebtn_settings.counter_frmt', $values['likebtn_settings_counter_frmt'])
      ->set('settings.likebtn_settings.share_size', $values['likebtn_settings_share_size'])
      ->set('settings.likebtn_settings.popup_width', $values['likebtn_settings_popup_width'])
      ->set('settings.likebtn_settings.tooltip_like_show_always', $values['likebtn_settings_tooltip_like_show_always'])
      ->set('settings.likebtn_settings.tooltip_dislike_show_always', $values['likebtn_settings_tooltip_dislike_show_always'])
      ->set('settings.likebtn_settings.popup_on_load', $values['likebtn_settings_popup_on_load'])
      ->set('settings.likebtn_settings.i18n_like', $values['likebtn_settings_i18n_like'])
      ->set('settings.likebtn_settings.i18n_dislike', $values['likebtn_settings_i18n_dislike'])
      ->set('settings.likebtn_settings.i18n_after_like', $values['likebtn_settings_i18n_after_like'])
      ->set('settings.likebtn_settings.i18n_after_dislike', $values['likebtn_settings_i18n_after_dislike'])
      ->set('settings.likebtn_settings.i18n_like_tooltip', $values['likebtn_settings_i18n_like_tooltip'])
      ->set('settings.likebtn_settings.i18n_dislike_tooltip', $values['likebtn_settings_i18n_dislike_tooltip'])
      ->set('settings.likebtn_settings.i18n_unlike_tooltip', $values['likebtn_settings_i18n_unlike_tooltip'])
      ->set('settings.likebtn_settings.i18n_undislike_tooltip', $values['likebtn_settings_i18n_undislike_tooltip'])
      ->set('settings.likebtn_settings.i18n_share_text', $values['likebtn_settings_i18n_share_text'])
      ->set('settings.likebtn_settings.i18n_popup_close', $values['likebtn_settings_i18n_popup_close'])
      ->set('settings.likebtn_settings.i18n_popup_text', $values['likebtn_settings_i18n_popup_text'])
      ->set('settings.likebtn_settings.i18n_popup_donate', $values['likebtn_settings_i18n_popup_donate'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
