<?php
/**
 * Copyright (C) 2013  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * Plugin Label Barcode Logo Warna by Erwan Setyo Budi (erwansetyobudi.librarian@gmail.com)
 */

/* Item barcode print */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB.'admin/default/session.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
  die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

$max_print = 12;

/* RECORD OPERATION */
if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
  if (!$can_read) {
    die();
  }
  if (!is_array($_POST['itemID'])) {
    // make an array
    $_POST['itemID'] = array((integer)$_POST['itemID']);
  }
  // loop array
  if (isset($_SESSION['barcodes'])) {
    $print_count = count($_SESSION['barcodes']);
  } else {
    $print_count = 0;
  }
  // barcode size
  $size = 2;
  // create AJAX request
  echo '<script type="text/javascript" src="'.JWB.'jquery.js"></script>';
  echo '<script type="text/javascript">';
  // loop array
  foreach ($_POST['itemID'] as $itemID) {
    if ($print_count == $max_print) {
      $limit_reach = true;
      break;
    }
    if (isset($_SESSION['barcodes'][$itemID])) {
      continue;
    }
    if (!empty($itemID)) {
      $barcode_text = trim($itemID);
      /* replace space */
      $barcode_text = str_replace(array(' ', '/', '\/'), '_', $barcode_text);
      /* replace invalid characters */
      $barcode_text = str_replace(array(':', ',', '*', '@'), '', $barcode_text);
      // send ajax request
      echo 'jQuery.ajax({ url: \''.SWB.'lib/phpbarcode/barcode.php?code='.$itemID.'&encoding='.$sysconf['barcode_encoding'].'&scale='.$size.'&mode=png\', type: \'GET\', error: function() { alert(\'Error creating barcode!\'); } });'."\n";
      // add to sessions
      $_SESSION['barcodes'][$itemID] = $itemID;
      $print_count++;
    }
  }
  echo 'top.$(\'#queueCount\').html(\''.$print_count.'\')';
  echo '</script>';
  // update print queue count object
  sleep(2);
  if (isset($limit_reach)) {
    $msg = str_replace('{max_print}', $max_print, __('Selected items NOT ADDED to print queue. Only {max_print} can be printed at once'));
    utility::jsAlert($msg);
  } else {
    utility::jsAlert(__('Selected items added to print queue'));
  }
  exit();
}

// clean print queue
if (isset($_GET['action']) AND $_GET['action'] == 'clear') {
  // update print queue count object
  echo '<script type="text/javascript">top.$(\'#queueCount\').html(\'0\');</script>';
  utility::jsAlert(__('Print queue cleared!'));
  unset($_SESSION['barcodes']);
  exit();
}

// barcode pdf download
if (isset($_GET['action']) AND $_GET['action'] == 'print') {
  // check if label session array is available
  if (!isset($_SESSION['barcodes'])) {
    utility::jsAlert(__('There is no data to print!'));
    die();
  }
  if (count($_SESSION['barcodes']) < 1) {
    utility::jsAlert(__('There is no data to print!'));
    die();
  }

  // concat all ID together
  $item_ids = '';
  foreach ($_SESSION['barcodes'] as $id) {
    $item_ids .= '\''.$id.'\',';
  }
  // strip the last comma
  $item_ids = substr_replace($item_ids, '', -1);
  // send query to database
  $item_q = $dbs->query('SELECT b.title, i.item_code, i.call_number FROM item AS i
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE i.item_code IN('.$item_ids.')');
  $item_data_array = array();
  while ($item_d = $item_q->fetch_row()) {
    if ($item_d[0]) {
      $item_data_array[] = $item_d;
    }
  }

  // include printed settings configuration file
  require SB.'admin'.DS.'admin_template'.DS.'printed_settings.inc.php';
  // check for custom template settings
  $custom_settings = SB.'admin'.DS.$sysconf['admin_template']['dir'].DS.$sysconf['template']['theme'].DS.'printed_settings.inc.php';
  if (file_exists($custom_settings)) {
    include $custom_settings;
  }

  // load print settings from database to override value from printed_settings file
  loadPrintSettings($dbs, 'barcode');

  // chunk barcode array
  $chunked_barcode_arrays = array_chunk($item_data_array, $sysconf['print']['barcode']['barcode_items_per_row']);
  // create html ouput
  $html_str = '<!DOCTYPE html>'."\n";
  $html_str .= '<html><head><title>Item Barcode & Label Print Result </title>'."\n";
  $html_str .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
  $html_str .= '<meta http-equiv="Pragma" content="no-cache" /><meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" /><meta http-equiv="Expires" content="Sat, 26 Jul 1997 05:00:00 GMT" />';
  $html_str .= '<style type="text/css">'."\n";
  $html_str .= 'body { padding: 0; margin: 1cm; font-family: '.$sysconf['print']['barcode']['barcode_fonts'].'; font-size: '.$sysconf['print']['barcode']['barcode_font_size'].'pt; background: #fff; }'."\n";
  $html_str .= '.labelStyle { padding-left:30px; width:13.5cm; height: 6cm; text-align: center; ; margin: '.$sysconf['print']['barcode']['barcode_items_margin'].'cm; border: '.$sysconf['print']['barcode']['barcode_border_size'].'px solid #000000;}'."\n";
  $html_str .= '.labelStyle1 { width: 13.5cm; height: 6cm; text-align: center; margin: '.'0'.'cm; }'."\n";
  $html_str .= '.barcode_rotate { position: relative; width:2.5cm; -moz-transform: rotate(-90deg); -webkit-transform: rotate(-90deg); -o-transform: rotate(-90deg); -ms-transform: rotate(-90deg); transform: rotate(-90deg);}'."\n";
  $html_str .= '.barcode_rotate_2 { position: relative; width:0cm; -moz-transform: rotate(0deg); -webkit-transform: rotate(0deg); -o-transform: rotate(0deg); -ms-transform: rotate(-90deg); transform: rotate(0deg);}'."\n";
  $html_str .= '</style>'."\n";
  $html_str .= '</head>'."\n";
  $html_str .= '<body>'."\n";
  $html_str .= '<a href="#" onclick="window.print()">Print Again</a>'."\n";
  $html_str .= '<table style="margin: 0; padding: 0;" cellspacing="0" cellpadding="0">'."\n";
  // loop the chunked arrays to row
  foreach ($chunked_barcode_arrays as $barcode_rows) {
        $html_str .= '<tr>'."\n";
        foreach ($barcode_rows as $barcode) {
		$html_str .= '<td valign="top">';
            $html_str .= '<td valign="top">';
            $html_str .= '<div class="labelStyle">';
            if ($sysconf['print']['barcode']['barcode_include_header_text']) {  }
				  $html_str .= '<table class="labelStyle1" >';
				  //Warna
				  $color_label = substr($barcode[2],0,1 );
				  	if 	   ($color_label==0){$warna = '#6666ff';}// biru
					elseif ($color_label==1){$warna = '#ffff66';}// kuning
					elseif ($color_label==2){$warna = '#66ff33';}// Hijau
					elseif ($color_label==3){$warna = '#999999';}// abu-abu
					elseif ($color_label==4){$warna = '#ff9933';}// orange
					elseif ($color_label==5){$warna = '#cc33ff';}// ungu
					elseif ($color_label==6){$warna = '#cc9966';}// krem
					elseif ($color_label==7){$warna = '#ff66cc';}// Pink
					elseif ($color_label==8){$warna = '#66ffff';}// toska
					elseif ($color_label==9){$warna = '#ff3333';}// merah
					else					{$warna = '#FFFFFF';}// putih
				  
				  $html_str .= '<tr>';
				  //Mengatur kolom barcode
				   if ($sysconf['print']['barcode']['barcode_cut_title']) {         			
				  	$html_str .= '<td rowspan="2" style="border-right: '.$sysconf['print']['barcode']['barcode_border_size'].'px solid #000000; padding-left:1cm; padding-top:2.5cm;"><div class="barcode_rotate">'.'<div style="font-size: 8pt; width: 3.5cm;">'.substr($barcode[0], 0, $sysconf['print']['barcode']['barcode_cut_title']).'...</div>'.'<img src="'.SWB.IMG.'/barcodes/'.str_replace(array(' '), '_', $barcode[1]).'.png" style="width: 5cm; height: 2.5cm; border="0"/>';
					} else { $html_str .= $barcode[0]; }
					//Mengatur Logo Barcode
				    if ($sysconf['print']['barcode']['barcode_cut_title']) {         			
				  	//$html_str .= '<td rowspan="2" style=" solid #000000; padding-left:px; padding-right:0cm;padding-bottom:4cm;"><div class="barcode_rotate_2">'.''.'<img height="60px" width="60px" src="'.SWB.'files/membercard/'.$sysconf['print']['membercard']['logo'].'" />';
					} else { $html_str .= $barcode[0]; }
					//Mengatur Kolom Header Call Number
				    $html_str .= '</td>';
				 $html_str .='<td style="background-color:'.$warna.'; border-bottom:0px solid #000000;"><div style="font-size: 12pt; ; padding-left: 25; font-weight:bold;">'.($sysconf['print']['barcode']['barcode_header_text1']).'</div><div style="font-size: 11pt;padding-left:20px; font-weight:none;">'.($sysconf['print']['barcode']['barcode_header_text2']).'</div><div style="font-size: 9pt; padding-left: 30px">'.($sysconf['print']['barcode']['barcode_header_text3']).'</div>';
				  $html_str .= '</td>';
			      
				  $html_str .= '</td></tr>';
				  $html_str .= '<tr><td>';
					$sliced_call_number = explode(' ', $barcode[2], 5);
	              	foreach ($sliced_call_number as $slice_call_number_label) {				  
				  	$html_str .= '<div style="'.$sysconf['print']['barcode']['barcode_call_number_style'].'">'.$slice_call_number_label.'</div>'; }
				  $html_str .= '</td>';
				  $html_str .= '</tr>';
				  $html_str .= '</table>';
			$html_str .= '</div>';
            $html_str .= '</td>';
        }
        $html_str .= '</tr>'."";
    }
    $html_str .= '</table>'."\n";
    $html_str .= '<script type="text/javascript">self.print();</script>'."\n";
    $html_str .= '</body></html>'."\n";
  // unset the session
  unset($_SESSION['barcodes']);
  // write to file
  $print_file_name = 'item_barcode_gen_print_result_'.strtolower(str_replace(' ', '_', $_SESSION['uname'])).'.html';
  $file_write = @file_put_contents(UPLOAD.$print_file_name, $html_str);
  if ($file_write) {
    // update print queue count object
    echo '<script type="text/javascript">parent.$(\'#queueCount\').html(\'0\');</script>';
    // open result in window
    echo '<script type="text/javascript">top.$.colorbox({href: "'.SWB.FLS.'/'.$print_file_name.'", iframe: true, width: 800, height: 500, title: "'.__('Item Barcodes Printing').'"})</script>';
  } else { utility::jsAlert('ERROR! Item barcodes failed to generate, possibly because '.SB.FLS.' directory is not writable'); }
  exit();
}

?>
<fieldset class="menuBox">
<div class="menuBoxInner printIcon">
  <div class="per_title">
	  <h2><?php echo __('Item Barcodes Printing'); ?></h2>
  </div>
  <div class="sub_section">
	  <div class="btn-group">
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/item_barcode_generator_logo.php?action=clear" class="notAJAX btn btn-default"><i class="glyphicon glyphicon-trash"></i>&nbsp;<?php echo __('Clear Print Queue'); ?></a>
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/item_barcode_generator_logo.php?action=print" class="notAJAX btn btn-default"><i class="glyphicon glyphicon-print"></i>&nbsp;<?php echo __('Print Barcodes for Selected Data');?></a>
	    <a href="<?php echo MWB; ?>bibliography/pop_print_settings.php?type=barcode" class="notAJAX btn btn-default openPopUp" title="<?php echo __('Change print barcode settings'); ?>"><i class="glyphicon glyphicon-wrench"></i></a>
	  </div>
    <form name="search" action="<?php echo MWB; ?>bibliography/item_barcode_generator_logo.php" id="search" method="get" style="display: inline;"><?php echo __('Search'); ?> :
    <input type="text" name="keywords" size="30" />
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="btn btn-default" />
    </form>
  </div>
  <div class="infoBox">
  <?php
  echo __('Maximum').' <font style="color: #f00">'.$max_print.'</font> '.__('records can be printed at once. Currently there is').' ';
  if (isset($_SESSION['barcodes'])) {
    echo '<font id="queueCount" style="color: #f00">'.count($_SESSION['barcodes']).'</font>';
  } else { echo '<font id="queueCount" style="color: #f00">0</font>'; }
  echo ' '.__('in queue waiting to be printed.');
  ?>
  </div>
</div>
</fieldset>
<?php
/* search form end */

// create datagrid
$datagrid = new simbio_datagrid();
/* ITEM LIST */
require SIMBIO.'simbio_UTILS/simbio_tokenizecql.inc.php';
require LIB.'biblio_list_model.inc.php';
// index choice
if ($sysconf['index']['type'] == 'index' || ($sysconf['index']['type'] == 'sphinx' && file_exists(LIB.'sphinx/sphinxapi.php'))) {
  if ($sysconf['index']['type'] == 'sphinx') {
    require LIB.'sphinx/sphinxapi.php';
    require LIB.'biblio_list_sphinx.inc.php';
  } else {
    require LIB.'biblio_list_index.inc.php';
  }
  // table spec
  $table_spec = 'item LEFT JOIN search_biblio AS `index` ON item.biblio_id=`index`.biblio_id';
  $datagrid->setSQLColumn('item.item_code',
        'item.item_code AS \''.__('Item Code').'\'',
		'biblio.title AS \''.__('Title').'\'',
        'item.call_number AS \''.__('Call Number').'\'',
		'item.last_update AS \''.__('Last Updated').'\'');
} else {
  require LIB.'biblio_list.inc.php';
  // table spec
  $table_spec = 'item LEFT JOIN biblio ON item.biblio_id=biblio.biblio_id';
  $datagrid->setSQLColumn('item.item_code',
        'item.item_code AS \''.__('Item Code').'\'',
		'biblio.title AS \''.__('Title').'\'',
        'item.call_number AS \''.__('Call Number').'\'',
		'item.last_update AS \''.__('Last Updated').'\'');
}
$datagrid->setSQLorder('item.last_update DESC');
// is there any search
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $keywords = $dbs->escape_string(trim($_GET['keywords']));
  $searchable_fields = array('title', 'author', 'subject', 'itemcode');
  $search_str = '';
  // if no qualifier in fields
  if (!preg_match('@[a-z]+\s*=\s*@i', $keywords)) {
    foreach ($searchable_fields as $search_field) {
      $search_str .= $search_field.'='.$keywords.' OR ';
    }
  } else {
    $search_str = $keywords;
  }
  $biblio_list = new biblio_list($dbs, 20);
  $criteria = $biblio_list->setSQLcriteria($search_str);
}
if (isset($criteria)) {
  $datagrid->setSQLcriteria('('.$criteria['sql_criteria'].')');
}
// set table and table header attributes
$datagrid->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// edit and checkbox property
$datagrid->edit_property = false;
$datagrid->chbox_property = array('itemID', __('Add'));
$datagrid->chbox_action_button = __('Add To Print Queue');
$datagrid->chbox_confirm_msg = __('Add to print queue?');
$datagrid->column_width = array('10%', '55%');
// set checkbox action URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, $can_read);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
  echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"<div>'.__('Query took').' <b>'.$datagrid->query_time.'</b> '.__('second(s) to complete').'</div></div>';
}
echo $datagrid_result;
/* main content end */
