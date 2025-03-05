<?php
/**
 * @package Unlimited Elements
 * @author UniteCMS http://unitecms.net
 * @copyright Copyright (c) 2016 UniteCMS
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

if(!defined('ABSPATH')) exit;

class UniteCreatorBreadcrumbs {

    /**
     * Get page items for breadcrumb
     */
    public function getBreadcrumbItems($params) {
        $items = array();

        $show_current = $this->getParamValueByKey('show_current', $params);
        $home_text = $this->getParamValueByKey('home_text', $params);
        $show_home = $this->getParamValueByKey('show_home', $params);

        $frontPageID = get_option('page_on_front');
        $currentPageID = get_queried_object_id();

        if($frontPageID && $frontPageID == $currentPageID) {
            if($show_current === 'true') {
                $items[] = array(
                    'text' => $home_text,
                    'url' => ''
                );
            }

            return $items;
        }

        if($show_home === 'true') {
            $items[] = array(
                'text' => $home_text,
                'url' => home_url('/')
            );
        }

        if(is_category() || is_single()) {
            $category = get_the_category();

            if(!empty($category)) {
                $category = UniteFunctionsUC::getArrFirstValue($category);
                $ancestors = get_ancestors($category->term_id, 'category');

                foreach(array_reverse($ancestors) as $ancestor) {
                    $ancestor_cat = get_term($ancestor, 'category');
                    if(!is_wp_error($ancestor_cat)) {
                        $items[] = array(
                            'text' => $ancestor_cat->name,
                            'url' => get_category_link($ancestor)
                        );
                    }
                }

                if(is_single()) {
                    $items[] = array(
                        'text' => $category->name,
                        'url' => get_category_link($category->term_id)
                    );
                }
            }

            if($show_current === 'true') {
                if(is_category()) {
                    $items[] = array(
                        'text' => single_cat_title('', false),
                        'url' => ''
                    );
                } elseif(is_single()) {
                    $items[] = array(
                        'text' => get_the_title(),
                        'url' => ''
                    );
                }
            }
        }
        elseif(is_page()) {
            global $post;

            $frontPageID = intval(get_option('page_on_front'));
            $currentPageID = intval($post->ID);

            if($currentPageID === $frontPageID) {
                return array();
            }

            if($post->post_parent) {
                $ancestors = array_reverse(get_post_ancestors($post));
                $ancestors = array_unique($ancestors);

                foreach($ancestors as $ancestor) {
                    if($ancestor !== $currentPageID) {
                        $items[] = array(
                            'text' => get_the_title($ancestor),
                            'url' => get_permalink($ancestor)
                        );
                    }
                }
            }

            if($show_current === 'true') {
                $items[] = array(
                    'text' => get_the_title(),
                    'url' => ''
                );
            }
        }
        elseif(is_tag()) {
            if($show_current === 'true') {
                $items[] = array(
                    'text' => single_tag_title('', false),
                    'url' => ''
                );
            }
        }
        elseif(is_author()) {
            if($show_current === 'true') {
                $items[] = array(
                    'text' => get_the_author(),
                    'url' => ''
                );
            }
        }
        elseif(is_archive()) {
            if($show_current === 'true') {
                if(is_day()) {
                    $items[] = array(
                        'text' => get_the_date(),
                        'url' => ''
                    );
                }
                elseif(is_month()) {
                    $items[] = array(
                        'text' => get_the_date('F Y'),
                        'url' => ''
                    );
                }
                elseif(is_year()) {
                    $items[] = array(
                        'text' => get_the_date('Y'),
                        'url' => ''
                    );
                }
            }
        }
        elseif(is_search()) {
            if($show_current === 'true') {
                $items[] = array(
                    'text' => sprintf('%s %s', __('Search Results for:'), get_search_query()),
                    'url' => ''
                );
            }
        }
        elseif(is_404()) {
            if($show_current === 'true') {
                $items[] = array(
                    'text' => __('Page Not Found'),
                    'url' => ''
                );
            }
        }

        return $items;
    }

    /**
     * Get Widget param value by key
     */
    private function getParamValueByKey($key, $data) {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        foreach($data as $item) {
            if($item['name'] == $key) {
                return $item['value'];
            }
        }

        return '';
    }
}