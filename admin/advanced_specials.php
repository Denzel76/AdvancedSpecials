<?php
/*
  Copyright (c) 2020, Denzel
  This work is licensed under a 
  Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
  You should have received a copy of the license along with this work.
  If not, see <http://creativecommons.org/licenses/by-nc-nd/4.0/>.
*/

  require('includes/application_top.php');
  
  prepDB();

  $currencies = new currencies();
  $_SESSION['currency'] = DEFAULT_CURRENCY;
  
  $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from manufacturers order by manufacturers_name");

  if ($number_of_rows = tep_db_num_rows($manufacturers_query)) {
    $manufacturers_array = array();
    $manufacturers_array[] = array('id' => '', 'text' => TEXT_ADVANCED_SPECIALS_MANUFACTURER);

    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {            
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }
  }

  $sort_fields = array(
    'product_id' => array(
        'text' => TEXT_ADVANCED_SPECIALS_SORT_PID,
        'field' => 'p.products_id'
    ),
    'product_model' => array(
        'text' => TEXT_ADVANCED_SPECIALS_SORT_PMODEL,
        'field' => 'p.products_model'
    ),
    'product_price' => array(
        'text' => TEXT_ADVANCED_SPECIALS_SORT_PPRICE,
        'field' => 'p.products_price'
    ),
    'product_status' => array(
        'text' => TEXT_ADVANCED_SPECIALS_SORT_PSTATUS,
        'field' => 'p.products_status'
    ),
    'product_name' => array(
        'text' => TEXT_ADVANCED_SPECIALS_SORT_PNAME,
        'field' => 'pd.products_name'
    )
  );

  $sort_list = array();
  
  foreach($sort_fields as $k => $v) {
    $sort_list[] = array(
        'id' => $k,
        'text' => $v['text']
    );
  }

  $product_name =  (isset($_GET['product_name']) && !empty($_GET['product_name']) ? trim($_GET['product_name']):null);

  $sort_type = (isset($_GET['sort_type']) && in_array($_GET['sort_type'], array('asc', 'desc')) ? $_GET['sort_type'] : 'asc');
  $sort = (isset($_GET['sort']) && isset($sort_fields[$_GET['sort']]['field']) ? $_GET['sort'] : 'product_id');
  $sort_field = $sort_fields[$sort]['field'] . ($sort != 'product_id' ? ' ' . $sort_type : '');

  $category_id = (isset($_GET['cPath']) && $_GET['cPath'] != '' ? intval($_GET['cPath']) : null);
  $subcats_flag = $_GET['subcats_flag'] ?? '0';
  $specials_flag = $_GET['specials_flag'] ?? '0';
  $sstatus_flag = $_GET['sstatus_flag'] ?? '0';
  $pstatus_flag = $_GET['pstatus_flag'] ?? '0';
  $category_tree_array[] = ['id' => '', 'text' => TEXT_ADVANCED_SPECIALS_CATEGORIE];
  $category_tree_array[] = ['id' => '0', 'text' => TEXT_TOP];

  $manufacturer_id = (isset($_GET['manufacturer_id']) && $_GET['manufacturer_id'] != '' ? intval($_GET['manufacturer_id']) : null);

  $discount = (isset($_GET['discount']) && !empty($_GET['discount']) ? trim($_GET['discount']):null);

  if($discount !== null) {
    if (substr($discount, -1) == '%') {
      $discount_percent = true;
    }
    $discount = floatval(preg_replace("/[^0-9.]/", "", str_replace($currencies->currencies[DEFAULT_CURRENCY]["decimal_point"], ".", $discount)));
  }

  $date = (isset($_GET['date']) && !empty($_GET['date']) ? trim($_GET['date']) . ' 23:59:59' : null);
  $start_date = (isset($_GET['start_date']) && !empty($_GET['start_date']) ? trim($_GET['start_date']) : null);

  $page = (isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']):1);

  $action = $_GET['action'] ?? 'list';

  if (tep_not_null($action)) {
    switch ($action) {

      // Enables/disables the special offer
      case 'setflag':
        specials_enhanced_set_status($_GET['id'], $_GET['flag']);

        tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action','flag','id'))));
      break;
        
      // Enables/disables the specials of the filtered products
      case 'setflag_all':
        $specials_flag = 1;
        $ids = specials_enhanced_get_all_id();
        foreach($ids as $id) {
          if($id['sid'] != null) specials_enhanced_set_status($id['sid'], $_GET['flag']);
        }
        tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action','flag','id'))));
      break;
        
      // Updates a single product/special offer
      case 'update':
        $id = $_GET['id'] ?? null;
        if($_GET['id'] && $discount !== null) specials_enhanced_update_product($id, $discount, $discount_percent ?? false, $date, $start_date);

        tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action','id','discount','date', 'start_date'))));
      break;

      // Updates all the filtered products/special offers
      case 'update_all':
        if($discount !== null || $date !== null || $start_date !== null) {
          if ($discount === null && $date != null) $specials_flag = 1;
          $ids = specials_enhanced_get_all_id();
          foreach($ids as $id) {
            specials_enhanced_update_product($id['pid'], $discount, $discount_percent ?? false, $date, $start_date);
          }
        }

        tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action','id','discount','date', 'start_date'))));
      break;
        
      // removes a single special offer
      case 'remove':
        $id = (isset($_GET['id']) ? intval($_GET['id']):0);
        tep_db_query("DELETE FROM specials WHERE products_id = $id");

        tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action','id'))));
      break;
        
      // removes all the filtered special offers
      case 'remove_all':
        $specials_flag = 1;
        $ids = specials_enhanced_get_all_id();

        foreach($ids as $id) {
          if($id['sid'] != null) tep_db_query("DELETE FROM specials WHERE specials_id = $id[sid]");
         }
         
         tep_redirect(tep_href_link('advanced_specials.php', tep_get_all_get_params(array('action'))));
      break;

      // Lists the filtered products
      case 'list':
        $specials_array = specials_enhanced_get_all_products($products_split, $products_query_numrows);
      break;
     
    }
}

  require('includes/template_top.php');
?>
  <h1 class="display-4 mb-3"><?= HEADING_TITLE; ?></h1>
  
    <form id="formular">
      <input name="action" type="hidden" id="action" value="list"/>
      <input type="hidden" name="flag" id="flag" value=""/>

      <div class="form-group row">
        <label for="product_name" class="col-form-label col-sm-2 text-left text-sm-right"><?= TEXT_ADVANCED_SPECIALS_FILTER; ?></label>
        <div class="col-3">
          <?= tep_draw_input_field('product_name', $product_name, 'placeholder="'.TEXT_ADVANCED_SPECIALS_NAME.'"'); ?>
         </div>
        <div class="col-3">
         <?= tep_draw_pull_down_menu('cPath', tep_get_category_tree( 0, '', '', $category_tree_array)); ?>
        </div>
        <div class="col-3">
          <?= tep_draw_pull_down_menu('manufacturer_id', $manufacturers_array); ?>
        </div>
      </div>
  
      <div class="form-group row">
        <label for="sort" class="col-form-label col-sm-2 text-left text-sm-right"><?= TEXT_ADVANCED_SPECIALS_SORT; ?></label>
        <div class="col-3">
          <?= tep_draw_pull_down_menu('sort', $sort_list, $sort, ''); ?>
         </div>
        <div class="col-3">
          <?= tep_draw_pull_down_menu('sort_type', array(array('id' => 'asc', 'text' => TEXT_ADVANCED_SPECIALS_ASC), array('id' => 'desc', 'text' => TEXT_ADVANCED_SPECIALS_DESC)), $sort_type, ''); ?>
        </div>
        <div class="col">
          <div class="form-check">
            <?= tep_draw_selection_field('subcats_flag', 'checkbox', '1', ($subcats_flag == '1' ? 'checked="checked"' : '')); ?>
             <label for="subcats_flag"><?= TEXT_ADVANCED_SPECIALS_INCLUDE_SUBCATEGORIES ?></label>
          </div>
          <div class="form-check">
            <?= tep_draw_selection_field('specials_flag', 'checkbox', '1', ($specials_flag == '1' ? 'checked="checked"' : ''), 'id="specials_flag" onclick="checkFlags(this);"'); ?>
            <label for="specials_flag"><?= TEXT_ADVANCED_SPECIALS_ONLY_SPECIALS ?></label>
            <?= tep_draw_selection_field('sstatus_flag', 'checkbox', '1', ($sstatus_flag == '1' ? 'checked="checked"' : ''), 'id="sstatus_flag" onclick="checkFlags(this);"'); ?>
            <label for="sstatus_flag"><?= TEXT_ADVANCED_SPECIALS_ONLY_EXPIRED_SPECIALS ?></label>
          </div>
          <div class="form-check">
            <?= tep_draw_selection_field('pstatus_flag', 'checkbox', '1', ($pstatus_flag == '1' ? 'checked="checked"' : '')); ?>
            <label for="pstatus_flag"><?= TEXT_ADVANCED_SPECIALS_ONLY_ACTIV_PRODUCTS ?></label>
          </div>
        </div>
        <div class="col mt-auto">
            <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_REMOVE_FILTER, 'fas fa-times-circle', tep_href_link('advanced_specials.php'), 'primary', array('type' => 'button'), 'btn-light btn-block align-self-end'); ?>
            <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_APPLY_FILTER, 'fas fa-filter', null, 'primary', null, 'btn-light btn-block align-self-end'); ?>
        </div>
      </div>
      <hr>
      
      <div class="form-group row">
        <label for="discount" class="col-form-label col-sm-2 text-left text-sm-right"><?= TEXT_ADVANCED_SPECIALS_DISCOUNT; ?></label>
        <div class="col-3">
          <?= tep_draw_input_field('discount', $discount, 'placeholder="'.TEXT_ADVANCED_SPECIALS_DISCOUNT_POC.'"'); ?>
        </div>
        <div class="col">
          <?= tep_draw_input_field('start_date', null, 'class="form-control date" id="specials_start_date" aria-describedby="pSDateHelp" placeholder="'.TEXT_ADVANCED_SPECIALS_DISCOUNT_STARTDATE.'"');?>
          <small id="pSDateHelp" class="form-text text-muted">
            <?= TEXT_ADVANCED_SPECIALS_DISCOUNT_DATE_HELP ?>
          </small>
        </div>
        <div class="col">
          <?= tep_draw_input_field('date', null, 'class="form-control date" id="specials_date" aria-describedby="pDateHelp" placeholder="'.TEXT_ADVANCED_SPECIALS_DISCOUNT_DATE.'"');?>
          <small id="pDateHelp" class="form-text text-muted">
            <?= TEXT_ADVANCED_SPECIALS_DISCOUNT_DATE_HELP ?>
          </small>
        </div>
        <div class="col">
          <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_DISCOUNT_APPLY, 'fas fa-funnel-dollar', null, null, array('params' => 'onclick="this.form.action.value=\'update_all\';this.form.submit();"'), 'btn-success btn-block'); ?>
        </div>
      </div>
      <div class="form-group row mt-3">
        <div class="col-2 text-right">
          <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_GENERAL_INFO, 'fas fa-info', null, null, array('type'=> 'button', 'params' => 'data-toggle="collapse" data-target="#advSpInfo" aria-expanded="false" aria-controls="advSpInfo"'), 'btn-sm btn-warning'); ?>
        </div>
        <div class="col">
          <?= TEXT_ADVANCED_SPECIALS_GENERAL_COMMENT ?>
        </div>
        <div class="col text-right">
          <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_ACTIVATE_ALL, 'fas fa-check', null, null, array('type'=> 'button', 'params' => 'data-action="setflag_all" data-flag="1" data-toggle="modal" data-target="#confirm"'), 'btn-success btn-sm'); ?>
          <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_DEACTIVATE_ALL, 'fas fa-times', null, null, array('type'=> 'button', 'params' => 'data-action="setflag_all" data-flag="0" data-toggle="modal" data-target="#confirm"'), 'btn-danger btn-sm'); ?>
          <?= tep_draw_bootstrap_button(TEXT_ADVANCED_SPECIALS_REMOVE_ALL, 'fas fa-trash', null, null, array('type'=> 'button', 'params' => 'data-action="remove_all" data-toggle="modal" data-target="#confirm"'), 'btn-danger btn-sm'); ?>
        </div>
      </div>
    </form>  

    <div class="row no-gutters">
      <div class="col-12">
        <div class="table-responsive">
          <table class="table table-striped table-hover .w-auto">
            <thead class="thead-dark">
              <tr>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_MODEL ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_PRODUCTS ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_PRICE . '<br/>(' . (DISPLAY_PRICE_WITH_TAX == 'true' ? TEXT_ADVANCED_SPECIALS_TABLE_HEADING_GROSS : TEXT_ADVANCED_SPECIALS_TABLE_HEADING_NET) . ')'; ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_DISCOUNTED_PRICE . '<br/>(' . (DISPLAY_PRICE_WITH_TAX == 'true' ? TEXT_ADVANCED_SPECIALS_TABLE_HEADING_GROSS : TEXT_ADVANCED_SPECIALS_TABLE_HEADING_NET) . ')'; ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_DISCOUNT_PERCENT ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_START_DATE ?></th>
                <th><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_DATE ?></th>
                <th class="text-right"><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_STATUS ?></th>
                <th class="text-right"><?= TEXT_ADVANCED_SPECIALS_TABLE_HEADING_ACTIONS ?></th>
              </tr>
            </thead>
            <tbody>
            <?php
              $n=0;
              foreach($specials_array as $specials) {
                $n++;
            ?>
            <form id="formular<?= $n ?>">
              <input type="hidden" name="action" id="action<?= $n ?>" value="update"/>
              <input type="hidden" name="id" value="<?= $specials['products_id'];?>"/>

              <tr>
                <td class="pt-3"><?= $specials['products_model'] ?></td>
                <td class="pt-3"><?= $specials['products_name'] ?></td>
                <td class="pt-3"><?= $currencies->display_price($specials['products_price'], tep_get_tax_rate_value($specials['products_tax_class_id'])); ?></td>
                <td><?= tep_draw_input_field('discount', ($specials['specials_new_products_price'] ? $currencies->display_price($specials['specials_new_products_price'], tep_get_tax_rate_value($specials['products_tax_class_id'])) : null), 'class="form-control text-danger"', 'text', false); ?></td>
                <td class="pt-3"><?= ($specials['specials_new_products_price']) ? number_format(-1*($specials['products_price'] - $specials['specials_new_products_price'])*100/$specials['products_price'], intval($currencies->currencies[DEFAULT_CURRENCY]["decimal_places"]), $currencies->currencies[DEFAULT_CURRENCY]["decimal_point"], $currencies->currencies[DEFAULT_CURRENCY]["thousands_point"]).'%' : '---'; ?></td>
                <td><?= tep_draw_input_field('start_date', (tep_not_null($specials['start_date']) ? substr($specials['start_date'], 0, 10) : ''), 'class="form-control date" onfocus="this.select();"', 'text', false); ?></td>
                <td><?= tep_draw_input_field('date', (tep_not_null($specials['expires_date']) ? substr($specials['expires_date'], 0, 10) : ''), 'class="form-control date" onfocus="this.select();"', 'text', false); ?></td>
                <td class="text-right pt-3"><?= (($specials['status'] != null) ? ($specials['status'] == '1') ? '<i class="fas fa-check-circle text-success"></i> <a href="' . tep_href_link('advanced_specials.php', 'action=setflag&flag=0&id=' . (int)$specials['specials_id']) . '&cPath=' . $category_id . '&manufacturer_id=' . $manufacturer_id . '&sort=' . $sort . '&sort_type=' . $sort_type . '&product_name=' . $product_name . '&subcats_flag=' . $subcats_flag . '&specials_flag=' . $specials_flag . '&sstatus_flag=' . $sstatus_flag . '&pstatus_flag=' . $pstatus_flag . '&page=' . $page . '"><i class="fas fa-times-circle text-muted"></i></a>' : '<a href="' . tep_href_link('advanced_specials.php', 'action=setflag&flag=1&id=' . (int)$specials['specials_id']) . '&cPath=' . $category_id . '&manufacturer_id=' . $manufacturer_id . '&sort=' . $sort . '&sort_type=' . $sort_type . '&product_name=' . $product_name . '&subcats_flag=' . $subcats_flag . '&specials_flag=' . $specials_flag . '&sstatus_flag=' . $sstatus_flag . '&pstatus_flag=' . $pstatus_flag . '&page=' . $page . '"><i class="fas fa-check-circle text-muted"></i></a> <i class="fas fa-times-circle text-danger"></i>' : '----') ?></td>
                <td class="text-right text-nowrap"><div class="btn-group" role="group"><?= tep_draw_bootstrap_button(null, 'fas fa-sync-alt', null, null, array('type'=>'submit'), 'btn-success btn-sm btn-update') . (($specials['specials_new_products_price'] != null) ?  tep_draw_bootstrap_button(null, 'fas fa-trash', null, null, array('type'=> 'button', 'params' => 'data-action="remove" data-row="' . $n . '" data-toggle="modal" data-target="#confirm"'), 'btn-danger btn-sm btn-trash') : tep_draw_bootstrap_button(null, 'fas fa-trash', null, null, array('type'=>'button'), 'btn-secondary btn-sm')); ?></div></td>
              </tr>
            </form>
            <?php
              }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="row my-1">
    <div class="col"><?= $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $page, TEXT_DISPLAY_NUMBER_OF_SPECIALS) ?></div>
    <div class="col text-right mr-2"><?= $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $page, tep_get_all_get_params(array('page', 'x', 'y'))) ?></div>
  </div>

  <?= TEXT_ADVANCED_SPECIALS_GENERAL_COMMENT; ?>

  <!-- Modal -->
  <div class="modal fade" id="confirm" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= TEXT_ADVANCED_SPECIALS_MODAL_CONFIRM; ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?= TEXT_ADVANCED_SPECIALS_GENERAL_CONFIRM; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TEXT_ADVANCED_SPECIALS_BUTTON_NO; ?></button>
          <button type="submit" class="btn btn-danger btn-ok"><?= TEXT_ADVANCED_SPECIALS_BUTTON_YES; ?></button>
        </div>
      </div>
    </div>
  </div>
      
  <script>
    function checkFlags(checkbox) {
      if (checkbox.checked == false) {
       $('#sstatus_flag').prop('checked', false);
      }     
      if (checkbox.checked == true) {
       $('#specials_flag').prop('checked', true);
      }
    }     
    $(document).ready(function() {
      $('.btn-update').tooltip({ title: "<?= TEXT_ADVANCED_SPECIALS_TABLE_UPDATE ?>" })
      $('.btn-trash').tooltip({ title: "<?= TEXT_ADVANCED_SPECIALS_TABLE_REMOVE ?>" })
      $('.date').datepicker({ dateFormat: 'yy-mm-dd' }).attr('autocomplete', 'off');
    });
    $('#confirm').on('show.bs.modal', function(e) {
      if (typeof($(e.relatedTarget).data('row')) != "undefined") {
        row = $(e.relatedTarget).data('row');
      } else {
        row = "";
      }
      $('#action' + row).val($(e.relatedTarget).data('action'));
      $('#flag').val($(e.relatedTarget).data('flag'));
      $(this).find('.btn-ok').attr('form', "formular" + row);
    });
  </script>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');

/* BOF FUNCTIONS *********************************************************************** */

/*
    checks whether the required db fields are available or creates them
*/
function prepDB() {
  $result = tep_db_query("SHOW COLUMNS FROM specials LIKE 'start_date'");
  if(!tep_db_num_rows($result)) {
   tep_db_query("ALTER TABLE specials ADD start_date DATETIME NULL DEFAULT NULL AFTER specials_last_modified");
  }
  $result = tep_db_query("SELECT * FROM hooks WHERE hooks_code = '_26_start_specials'");
  if(!tep_db_num_rows($result)) {
   tep_db_query("INSERT INTO hooks (hooks_id, hooks_site, hooks_group, hooks_action, hooks_code, hooks_class, hooks_method) VALUES (NULL, 'shop', 'system', 'startApplication', '_26_start_specials', '', 'adv_specials::start')");
  }
}
/*
    gets the id list of the filtered products
*/
function specials_enhanced_get_all_id() {
  global $category_id, $subcats_flag, $manufacturer_id, $specials_flag, $product_name, $languages_id;

  $tables = array();
    
  if($specials_flag) {
    $tables[] = 'products p INNER JOIN specials s ON p.products_id = s.products_id';
  } else {
    $tables[] = 'products p LEFT JOIN specials s ON p.products_id = s.products_id';
  }
    
  $clauses = array();

  if ($product_name != null) {
    $tables[] = ' INNER JOIN products_description pd ON p.products_id = pd.products_id';
    $clauses[] = "pd.products_name LIKE '%" .  $product_name . "%'";
    $clauses[] = 'pd.language_id = '. (int)$languages_id;
  }
    
  if($category_id !== null && $category_id >= 0) {
    $tables[] = 'INNER JOIN products_to_categories p2c ON p.products_id = p2c.products_id';

    if($subcats_flag) {
      $categories_array = tep_get_category_tree($category_id,'','0','', true);
      $cats = array();
        
      foreach($categories_array as $cat) {
        $cats[] = $cat['id'];
      }
      $clauses[] = "p2c.categories_id IN (" . implode(',',$cats) . ")";
    } else {
      $clauses[] = "p2c.categories_id = '$category_id'";
    }
  }
    
  if($manufacturer_id !== null) {
    $clauses[] = "p.manufacturers_id = '$manufacturer_id'";
  }
    
  $tables_text = implode(' ', $tables);
  $clauses_text = '1=1';

  if(count($clauses) > 0) {
    $clauses_text = implode(' AND ', $clauses);
  }

  $ids_query = tep_db_query("SELECT p.products_id AS pid, s.specials_id AS sid FROM $tables_text WHERE $clauses_text");
  $ids = array();
    
    while($id = tep_db_fetch_array($ids_query)) {
    $ids[] = $id;
  }

  return $ids;
}

/*
    gets all the filtered products
*/
function specials_enhanced_get_all_products(&$products_split, &$products_query_numrows) {
  global $category_id, $subcats_flag, $manufacturer_id, $specials_flag, $sstatus_flag, $pstatus_flag, $languages_id, $page, $sort_field, $product_name;

  $tables = array();
  $clauses = array();

  $fields = 'p.products_id, p.products_price, p.products_tax_class_id, p.products_model, pd.products_name, s.specials_id, s.specials_new_products_price, s.expires_date, s.status, s.start_date';
    
  if($specials_flag) {
    $tables[] = 'products  p INNER JOIN specials s ON p.products_id = s.products_id INNER JOIN products_description pd ON p.products_id = pd.products_id';
  } else {
    $tables[] = 'products p LEFT JOIN specials s ON p.products_id = s.products_id INNER JOIN products_description pd ON p.products_id = pd.products_id';
  }
    
  $clauses[] = 'pd.language_id = '. (int)$languages_id;
    
  if($category_id !== null && $category_id >= 0) {
    $tables[] = 'INNER JOIN products_to_categories p2c ON p.products_id = p2c.products_id';

    if($subcats_flag) {
      $categories_array = tep_get_category_tree($category_id,'','0','', true);
      $cats = array();
      
      foreach($categories_array as $cat) {
        $cats[] = $cat['id'];
      }
    
      $clauses[] = "p2c.categories_id IN (" . implode(',',$cats) . ")";
    } else {
      $clauses[] = "p2c.categories_id = '$category_id'";
    }
  }

  if($pstatus_flag) {
    $clauses[] = "p.products_status = '1'";
  }
    
  if($sstatus_flag) {
    $clauses[] = "s.expires_date < now()";
  }
    
  if($manufacturer_id !== null && $manufacturer_id > 0) {
    $clauses[] = "p.manufacturers_id = '$manufacturer_id'";
  }
    
  if ($product_name != null) {
    $clauses[] = "pd.products_name LIKE '%" .  $product_name . "%'";
  }
    
  $tables_text = implode(' ', $tables);
  $clauses_text = '1=1';

  if(count($clauses) > 0) $clauses_text = implode(' AND ', $clauses);

  $products_query_text = "select $fields from $tables_text WHERE $clauses_text ORDER BY $sort_field";
    
  $products_split = new splitPageResults($page, MAX_DISPLAY_SEARCH_RESULTS, $products_query_text, $products_query_numrows);
  $products_query = tep_db_query($products_query_text);
  $products = array();

  while ($product = tep_db_fetch_array($products_query)) {
    $products[] = $product;
  }
    
  return $products;
}

/*
    Sets a product special status
*/
function specials_enhanced_set_status($specials_id, $status) {
  tep_db_query("UPDATE specials SET status = '" . (int)$status . "'" . ($status == 0 ? ", start_date = NULL" : "") . ", date_status_change = now() WHERE specials_id = " . (int)$specials_id);
}

/*
    Updates a product special offer
*/
function specials_enhanced_update_product($product_id, $discount = null, $discount_percent = false, $date = null, $start_date = null) {
  $product_query = tep_db_query("SELECT p.products_id AS id, p.products_price AS price, p.products_tax_class_id AS tax FROM products p WHERE p.products_id = $product_id");
  $product = tep_db_fetch_array($product_query);

  $fields = array();
    
  if($discount !== null) {
    if($discount_percent) {
       $discounted_price = ($product['price'] - (($discount / 100) * $product['price']));
    } elseif (DISPLAY_PRICE_WITH_TAX == 'true') {
      $discounted_price = floatval($discount/(1 + tep_get_tax_rate_value($product['tax'])/100));
    } else {
      $discounted_price = floatval($discount);
    }

    $fields['specials_new_products_price'] = $discounted_price;
  }

  if (tep_not_null($date)) {
    $fields['expires_date'] = $date;
  }

  if (tep_not_null($start_date)) {
    $fields['start_date'] = $start_date;
  }

  if(tep_db_num_rows(tep_db_query("SELECT specials_id FROM specials WHERE products_id = $product_id")) == 1) {
    $set_fields = array();

    foreach($fields as $k => $v) {
      $set_fields[] = "$k = '$v'";
    }
 
    $set_fields = implode(', ', $set_fields);
    $set_fields .= ", specials_last_modified = now()";

    tep_db_query("UPDATE specials SET $set_fields WHERE products_id = '$product_id'");
  } else {
    $key_fields = array();
    $value_fields = array();

    foreach($fields as $k => $v) {
      $key_fields[] = $k;
      $value_fields[] = "'$v'";
    }
        
    $key_fields = implode(', ', $key_fields);
    $value_fields = implode(', ', $value_fields);

    tep_db_query("INSERT INTO specials (products_id, specials_date_added, status, $key_fields) VALUES ('" . (int)$product_id . "', now(), '0', $value_fields)");
  }

  return true;
}

/* EOF FUNCTIONS *********************************************************************** */
?>
