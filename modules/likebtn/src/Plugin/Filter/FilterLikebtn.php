<?php

namespace Drupal\likebtn\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\likebtn\LikeBtnMarkup;

/**
 * @Filter(
 *   id = "filter_likebtn",
 *   title = @Translation("Enable LikeBtn shortcodes"),
 *   description = @Translation("Shortcode example: [likebtn]"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterLikebtn extends FilterBase {

  const LIKEBTN_SHORTCODE = 'likebtn';

  public function process($text, $langcode) {
    
    $markup_render = new LikeBtnMarkup();

    $replacements = array();

    $regex = '/(?<!\<code\>)\[' . self::LIKEBTN_SHORTCODE . '([^}\n]*?)\](?!\<\/code\>)/is';
    preg_match_all($regex, $text, $matches);

    // Found shortcodes.
    if (!empty($matches[1])) {

      // Parse options.
      foreach ($matches[1] as $index => $params_str) {
        $regex_list[$index] = $regex;
        $replacements[$index] = '';

        $regex_params = '/(\w+)\s*=\s*\"(.*?)\"/si';
        preg_match_all($regex_params, $params_str, $matches_params);

        if (!count($matches_params)) {
          continue;
        }

        $settings = array();
        foreach ($matches_params[1] as $matches_params_index => $option) {
          //$settings[$option] = _likebtn_prepare_option($option, $matches_params[2][$matches_params_index]);
          $settings[$option] = $matches_params[2][$matches_params_index];
        }

        // Get button markup.
        $markup = $markup_render->likebtn_render_markup('', '', $settings, FALSE, FALSE);
        $replacements[$index] = $markup;
      }

      $text = preg_replace($regex_list, $replacements, $text, 1);
    }

    return new FilterProcessResult($text);
  }
}
