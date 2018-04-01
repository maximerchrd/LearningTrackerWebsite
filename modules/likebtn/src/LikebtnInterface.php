<?php

namespace Drupal\likebtn;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface LikebtnInterface extends ConfigEntityInterface {
  /**
   * Module name
   */
  const LIKEBTN_MODULE_NAME = 'likebtn';
  const LIKEBTN_VERSION = '2.4';

  /**
   * Views widget display oprions
   */
  const LIKEBTN_VIEWS_WIDGET_DISPLAY_ONLY = 1;
  const LIKEBTN_VIEWS_WIDGET_FULL = 2;

  /**
   * LikeBtn plans
   */
  const LIKEBTN_PLAN_TRIAL = 9;
  const LIKEBTN_PLAN_FREE = 0;
  const LIKEBTN_PLAN_PLUS = 1;
  const LIKEBTN_PLAN_PRO = 2;
  const LIKEBTN_PLAN_VIP = 3;
  const LIKEBTN_PLAN_ULTRA = 4;

  /**
   * Comments sort by
   */
  const LIKEBTN_COMMENTS_SORT_BY_LIKES = 'likes';
  const LIKEBTN_COMMENTS_SORT_BY_DISLIKES = 'dislike';
  const LIKEBTN_COMMENTS_SORT_BY_LIKES_MINUS_DISLIKES = 'likes_minus_dislikes';

  /**
   * Comments sort order
   */
  const LIKEBTN_COMMENTS_SORT_ORDER_ASC = 'asc';
  const LIKEBTN_COMMENTS_SORT_ORDER_DESC = 'desc';

  /**
   * LikeBtn shortcode
   */
  const LIKEBTN_SHORTCODE = 'likebtn';

  /**
   * Voting API tag name
   */
  const LIKEBTN_VOTING_TAG = 'likebtn';

  /**
   * Voting API vote source of the vote cast on the entity
   */
  const LIKEBTN_VOTING_VOTE_SOURCE = 'likebtn_entity';

  /**
   * Another
   */
  const LIKEBTN_LAST_SUCCESSFULL_SYNC_TIME_OFFSET = 57600;
  const LIKEBTN_LOCALES_SYNC_INTERVAL = 57600;
  const LIKEBTN_STYLES_SYNC_INTERVAL = 57600;
  const LIKEBTN_API_URL = 'http://api.likebtn.com/api/';
}
