<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  class adv_specials extends specials {

////
// Auto start products on special
    public static function start() {
      return tep_db_query("UPDATE specials SET status = 1, date_status_change = NOW() WHERE status = 0 AND NOW() >= start_date AND start_date > 0");
    }

  }
