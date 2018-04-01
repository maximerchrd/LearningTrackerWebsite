<?php

namespace Drupal\likebtn;

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\Entity;
use Drupal\votingapi\Entity\VoteType;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

class LikeBtn {
  protected static $synchronized = FALSE;
  // Cached API request URL.
  protected static $apiurl = '';
  public $config;

  /**
   * Constructor.
   */
  public function __construct() {
    //$this->config = \Drupal::config('likebtn.settings');
    $this->config = \Drupal::configFactory()->getEditable('likebtn.settings');
  }

  /**
   * Running votes synchronization.
   */
  public function runSyncVotes() {
    if (!self::$synchronized && $this->config->get('general.likebtn_account_data_email')
      && $this->config->get('general.likebtn_account_data_api_key')
      && $this->timeToSyncVotes($this->config->get('general.likebtn_sync_inerval') * 60)
      && function_exists('curl_init')) {
      $this->syncVotes($this->config->get('general.likebtn_account_data_email'), $this->config->get('general.likebtn_account_data_api_key'), $this->config->get('general.likebtn_account_data_site_id'));
    }
  }

  /**
   * Check if it is time to sync votes.
   */
  public function timeToSyncVotes($sync_period) {

    $last_sync_time = $this->config->get('sync.likebtn_last_sync_time');

    $now = time();
    if (!$last_sync_time) {
      $this->config->set('sync.likebtn_last_sync_time', $now);
      self::$synchronized = TRUE;
      return TRUE;
    }
    else {
      if ($last_sync_time + $sync_period > $now) {
        return FALSE;
      }
      else {
        $this->config->set('sync.likebtn_last_sync_time', $now);
        self::$synchronized = TRUE;
        return TRUE;
      }
    }
  }

  /**
   * Retrieve data.
   */
  public function curl($url) {
    $drupal_version = \Drupal::VERSION;
    $likebtn_version = LikebtnInterface::LIKEBTN_VERSION;
    $php_version = phpversion();
    $useragent = "Drupal $drupal_version; module $likebtn_version; PHP $php_version";

    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
    catch(\Exception $e) {

    }
  }

  /**
   * Comment sync function.
   */
  public function syncVotes($email = '', $api_key = '', $site_id = '') {
    $sync_result = TRUE;

    $last_sync_time = number_format($this->config->get('sync.likebtn_last_sync_time'), 0, '', '');

    $updated_after = '';

    if ($this->config->get('sync.likebtn_last_successfull_sync_time')) {
      $updated_after = $this->config->get('sync.likebtn_last_successfull_sync_time') - LikebtnInterface::LIKEBTN_LAST_SUCCESSFULL_SYNC_TIME_OFFSET;
    }

    $url = "output=json&last_sync_time=" . $last_sync_time;
    if ($updated_after) {
      $url .= '&updated_after=' . $updated_after;
    }

    // Retrieve first page.
    $response = $this->apiRequest('stat', $url, $email, $api_key, $site_id);
    $this->updateVotes($response);
    if (!isset($response['response']['items'])) {
      $sync_result = FALSE;
    }

    // Retrieve all pages.
    if (isset($response['response']['total']) && isset($response['response']['page_size'])) {
      $total_pages = ceil((int) $response['response']['total'] / (int) $response['response']['page_size']);

      for ($page = 2; $page <= $total_pages; $page++) {
        $response = $this->apiRequest('stat', $url . '&page=' . $page, $email, $api_key, $site_id);

        $this->updateVotes($response);
        if (!isset($response['response']['items'])) {
          $sync_result = FALSE;
        }
      }
    }

    if ($sync_result) {
      $this->config->set('sync.likebtn_last_successfull_sync_time', $last_sync_time);
    }
  }

  /**
   * Test synchronization.
   */
  public function testSync($email, $api_key, $site_id) {
    $email = trim($email);
    $api_key = trim($api_key);
    $site_id = trim($site_id);

    $response = $this->apiRequest('stat', 'output=json&page_size=1', $email, $api_key, $site_id);

    return $response;
  }

  /**
   * Decode JSON.
   */
  public function jsonDecode($jsong_string) {
    return json_decode($jsong_string, TRUE);
  }

  /**
   * Update votes in database from API response.
   */
  public function updateVotes($response) {
    $votes = array();

    if (!empty($response['response']['items'])) {
      foreach ($response['response']['items'] as $item) {

        $entity_type = '';
        $entity_id = '';
        $field_id = '';
        $field_index = 0;

        // Parse identifier.
        if (strstr($item['identifier'], '_field_')) {
          // Item is a field.
          preg_match('/^(.*)_(\d+)_field_(.*?)(?:_index_(\d+))?$/', $item['identifier'], $identifier_parts);

          if (!empty($identifier_parts[1])) {
            $entity_type = $identifier_parts[1];
          }
          else {
            continue;
          }

          if (!empty($identifier_parts[2])) {
            $entity_id = $identifier_parts[2];
          }
          else {
            continue;
          }

          if (!empty($identifier_parts[3])) {
            $field_id = $identifier_parts[3];
          }
          else {
            continue;
          }

          if (!empty($identifier_parts[4])) {
            $field_index = $identifier_parts[4];
          }
        }
        else {
          // Item is an entity.
          preg_match('/^(.*)_(\d+)$/', $item['identifier'], $identifier_parts);

          if (!empty($identifier_parts[1])) {
            $entity_type = $identifier_parts[1];
          }
          else {
            continue;
          }
          if (!empty($identifier_parts[2])) {
            $entity_id = $identifier_parts[2];
          }
          else {
            continue;
          }
        }

        $vote_source = LikebtnInterface::LIKEBTN_VOTING_VOTE_SOURCE;
        if ($field_id) {
          $vote_source = 'likebtn_'.$entity_type.'_'.$entity_id.'_field_' . $field_id . '_index_' . $field_index;
        }
        $likes = 0;
        if (!empty($item['likes'])) {
          $likes = $item['likes'];
        }
        $dislikes = 0;
        if (!empty($item['dislikes'])) {
          $dislikes = $item['dislikes'];
        }

        // If vote for this entity/field has been already stored - continue.
        foreach ($votes as $vote) {
          if ($vote['entity_type'] == $entity_type && $vote['entity_id'] == $entity_id && $vote['vote_source'] == $vote_source) {
            continue 2;
          }
        }

        // Get entity info.
        try {
          // Get a node storage object.
          $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);

          if (empty($entity_storage)) {
            continue;
          }

          // Load a single node.
          $entity = $entity_storage->load($entity_id);

          //list($tmp_entity_id, $entity_revision_id, $bundle) = entity_extract_ids($entity_type, $entity);
          $entity_values = $entity->toArray();

          $bundle = $entity_values['type'][0]['target_id'];
        }
        catch (\Exception $e) {
          continue;
        }

        // Likes and Disliked stored in Voting API.
        $votes[] = array(
          'entity_type' => $entity_type,
          'entity_id' => $entity_id,
          'value_type' => 'points',
          'value' => $likes,
          'tag' => LikebtnInterface::LIKEBTN_VOTING_TAG,
          'user_id' => 0,
          'vote_source' => $vote_source,
          'type' => $bundle,
        );
        $votes[] = array(
          'entity_type' => $entity_type,
          'entity_id' => $entity_id,
          'value_type' => 'points',
          'value' => $dislikes * (-1),
          'tag' => LikebtnInterface::LIKEBTN_VOTING_TAG,
          'user_id' => 0,
          'vote_source' => $vote_source,
          'type' => $bundle,
        );

        if ($field_id) {
          $langcode = $entity_values['langcode'][0]['value'];

          // Get entity LikeBtn fields.
          $entity_fields = $entity->getFields();

          // Set field value.
          $likes_minus_dislikes = $likes - $dislikes;

          foreach ($entity_fields as $field_name => $field_info) {

            if (preg_replace("/^field_/", '', $field_name) != $field_id) {
              continue;
            }

            $definition = $field_info->getFieldDefinition();

            if ($definition->getType() != 'likebtn_field') {
              continue;
            }

            $field_fields_data = array(
              //'entity_type' => $entity_type,
              'bundle' => $bundle,
              'entity_id' => $entity_id,
              'revision_id' => $entity_id,
              'delta' => $field_index,
              'langcode' => !empty($langcode) ? $langcode : Language::LANGCODE_NOT_SPECIFIED,
            );
            $field_fields_data[$field_name . '_likebtn_likes'] = $likes;
            $field_fields_data[$field_name . '_likebtn_dislikes'] = $dislikes;
            $field_fields_data[$field_name . '_likebtn_likes_minus_dislikes'] = $likes_minus_dislikes;

            $fields_table_name = 'node__' . $field_name;

            try {
              // Insert value.
              \Drupal::database()
                ->insert($fields_table_name)
                ->fields($field_fields_data)
                ->execute();
            }
            catch (\Exception $e) {
              // \Drupal::logger('likebtn')->error('Sync filed error. Field data: '.json_encode($field_fields_data).'. '.$e->getMessage());
              // Update value.
              try {
                \Drupal::database()->update($fields_table_name)
                  ->fields($field_fields_data)
                  //->condition('entity_type', $entity_type)
                  ->condition('delta', $field_index)
                  ->condition('bundle', $bundle)
                  ->condition('entity_id', $entity_id)
                  ->execute();
                // $query = \Drupal::database()->insert('field_data_' . $field_name);
                // $query->fields(array(
                //   'entity_type',
                //   'bundle',
                //   'entity_id'
                // ));
                // $query->values(array(
                //   $entity_type,
                //   $bundle,
                //   $entity_id
                // ));
                // $query->execute();
              }
              catch (\Exception $e) {
                \Drupal::logger('likebtn')->error('Sync filed error. Field data: '.json_encode($field_fields_data).'. '.$e->getMessage());
              }
            }
          }
        }
      }

      if ($votes) {
        $vote_storage = \Drupal::entityManager()->getStorage('vote');
        //$vote_query = $vote_storage->getQuery();
        
        foreach ($votes as $vote) {

          $vote_query = \Drupal::entityQuery('vote');
          if ((int)$vote['value'] > 0) {
            $vote_ids = $vote_query
              ->condition('entity_type', $vote['entity_type'])
              ->condition('entity_id', $vote['entity_id'])
              ->condition('vote_source', $vote['vote_source'])
              ->condition('value', 0, '>=')
              ->execute();
          } else {
            $vote_ids = $vote_query
              ->condition('entity_type', $vote['entity_type'])
              ->condition('entity_id', $vote['entity_id'])
              ->condition('vote_source', $vote['vote_source'])
              ->condition('value', 0, '<=')
              ->execute();
          }

          entity_delete_multiple('vote', $vote_ids);

          $vote = $vote_storage->create($vote);
          $vote->save();

          // $query = $vote_query
          //   ->condition('entity_type', $vote['entity_type'])
          //   ->condition('entity_id', $vote['entity_id'])
          //   ->condition('vote_source', $vote['vote_source']);
          // $vote_uids = $query->execute();
          // if (count($vote_uids) > 1) {
          //   echo "<pre>";
          //   print_r($vote);
          //   print_r($vote_uids);
          //   exit();
          // }
          // foreach ($vote_storage->loadMultiple($vote_uids) as $vote_entity) {
          //   $vote_entity->delete();
          // }

          // $criteria[] = array(
          //   'entity_type' => $vote['entity_type'],
          //   'entity_id' => $vote['entity_id'],
          //   'value_type' => $vote['value_type'],
          //   'tag' => $vote['tag'],
          //   'uid' => $vote['uid'],
          //   'vote_source' => $vote['vote_source'],
          // );
        }

        return TRUE;
      }
      return FALSE;
    }
  }

  /**
   * Run locales synchronization.
   */
  /*public function runSyncLocales() {
    if ($this->timeToSync(LikebtnInterface::LIKEBTN_LOCALES_SYNC_INTERVAL, 'likebtn_last_locale_sync_time') && function_exists('curl_init')) {
      $this->syncLocales();
    }
  }*/

  /**
   * Run styles synchronization.
   */
  /*public function runSyncStyles() {
    if ($this->timeToSync(LikebtnInterface::LIKEBTN_STYLES_SYNC_INTERVAL, 'likebtn_last_style_sync_time') && function_exists('curl_init')) {
      $this->syncStyles();
    }
  }*/

  /**
   * Check if it is time to sync locales.
   */
  /*public function timeToSync($sync_period, $sync_variable) {

    $last_sync_time = $this->config->get($sync_variable) ?: 0;

    $now = time();
    if (!$last_sync_time) {
      $this->config->set($sync_variable, $now);
      return TRUE;
    }
    else {
      if ($last_sync_time + $sync_period > $now) {
        return FALSE;
      }
      else {
        $this->config->set($sync_variable, $now);
        return TRUE;
      }
    }
  }*/

  /**
   * Locales sync function.
   */
  /*public function syncLocales() {
    $url = LikebtnInterface::LIKEBTN_API_URL . "?action=locale";

    $response_string = $this->curl($url);
    $response = $this->jsonDecode($response_string);

    if (isset($response['result']) && $response['result'] == 'success' && isset($response['response']) && count($response['response'])) {
      $this->config->set('sync.likebtn_locales', $response['response']);
    }
  }*/

  /**
   * Styles sync function.
   */
  /*public function syncStyles() {
    $url = LikebtnInterface::LIKEBTN_API_URL . "?action=style";

    $response_string = $this->curl($url);
    $response = $this->jsonDecode($response_string);

    if (isset($response['result']) && $response['result'] == 'success' && isset($response['response']) && count($response['response'])) {
      $this->config->set('sync.likebtn_styles', $response['response']);
    }
  }*/

  /**
   * Request to API.
   */
  public function apiRequest($action, $request, $email = '', $api_key = '', $site_id = '') {
    if (!self::$apiurl) {
      if (!$email) {
        $email  = trim($this->config->get('general.likebtn_account_data_email'));
      }
      if (!$api_key) {
        $api_key = trim($this->config->get('general.likebtn_account_data_api_key'));
      }
      if (!$site_id) {
        $site_id = trim($this->config->get('general.likebtn_account_data_site_id'));
      }

      if ($site_id) {
        $domain_site_id = "site_id={$site_id}&";
      }/* else {
        $subdirectory = trim($this->config->get('sync.likebtn_settings_subdirectory'));
        $local_domain = trim($this->config->get('sync.likebtn_settings_local_domain'));
        if ($local_domain) {
          $domain_site_id = "domain={$local_domain}&";
        }
        elseif ($subdirectory) {
          $parse_url    = UrlHelper::parse(Url::fromRoute(NULL, array('absolute' => TRUE)));
          $domain       = $parse_url['host'] . $subdirectory;
          $domain_site_id = "domain={$domain}&";
        }
      }*/

      self::$apiurl = LikebtnInterface::LIKEBTN_API_URL . "?email={$email}&api_key={$api_key}&nocache=.php&source=drupal&" . $domain_site_id;
    }
    $url = self::$apiurl . "action={$action}&" . $request;

    $response_string = $this->curl($url);

    $response = $this->jsonDecode($response_string);

    return $response;
  }
}
