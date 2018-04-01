<?php

namespace Drupal\likebtn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\likebtn\Controller\LikeBtnController;
use Drupal\likebtn\LikebtnInterface;
use Drupal\Core\Url;

class LikebtnReportsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'likebtn.reports'
    ];
  }

  public function getFormId() {
    return 'likebtn.reports';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('likebtn.settings');

    $site_id = $config->get('general.likebtn_account_data_site_id');
    $loader_src = /*drupal_get_path('module', LikebtnInterface::LIKEBTN_MODULE_NAME).*/'/modules/likebtn/assets/img/ajax_loader_white.gif';

    $form['#attached']['library'][] = 'likebtn/likebtn-reportslibraries';

    $form['reports'] = array(
      '#theme'   => 'likebtn_admin_reports',
      '#site_id' => $site_id,
      '#loader_src' => $loader_src,
      '#settings_url' => Url::fromRoute('likebtn.admin')->toString(),
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

}
