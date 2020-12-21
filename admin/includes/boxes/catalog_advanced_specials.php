<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array('code' => 'advanced_specials.php',
                               'title' => MODULES_ADMIN_MENU_CATALOG_ADVANCED_SPECIALS,
                               'link' => tep_href_link('advanced_specials.php'));

      break;
    }
  }
