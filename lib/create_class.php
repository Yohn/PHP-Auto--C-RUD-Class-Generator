<?php
/*
! This script was originally created in 2011 to help speed up
!			some development and coding processes. 
!			I stopped using it shortly after because I was originally using mysql_* functions.
!			Recently I remembered I did this and wanted to convert it to PDO and use it again.

TODO: Clean up, make OOP, auto build forms and JS.

--   Keys are not being processed correctly,
--       currently only selects by an id column, regardless if one is found or not.


#lets try to generate a php class, by simply submitting the create table function
*/

$sql = 'CREATE TABLE `pages` (
  `id` bigint(255) NOT NULL,
  `name` varchar(300) NOT NULL,
  `Sname` varchar(200) NOT NULL,
  `Sdesc` varchar(300) NOT NULL,
  `keys` varchar(300) NOT NULL,
  `contents` text NOT NULL,
  `pics` text NOT NULL,
  `options` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;';



$createTb = strstr($sql, '(', true);
$createSub = strstr($sql, '(');
$rows = explode(',', $createSub);
preg_match('/`(.+?)`/i', $createTb, $tbName);
echo '<pre>tbName = '.$tbName[1].'
<!--createTb = '.$createTb.'-->
createSub = '.$createSub;
$totalRows = count($rows);
// fix last row..
$lastR = explode("\n", trim($rows[$totalRows-1]));
$rows[$totalRows-1] = $lastR[0];
// fix first row
$firstR = explode("\n", $rows[0]);
$rows[0] = $firstR[1];
foreach($rows as $k => $v){
  $tr = trim($v);
  $ex = explode(' ', $tr);
	
  if(substr($tr, 0, 1) == '`'){
		if(substr($ex[1], 0, 3) == 'int'){
			$length['int'] = str_replace(['int(', ')'], '', $ex[1]);
		} else if(substr($ex[1], 0, 3) == 'var'){
			$length['var'] = str_replace(['varchar(', ')'], '', $ex[1]);
		}
    // the first character is a ` so its a column in the table.
    $colName = str_replace('`', '', $ex[0]);
    $columns[$colName] = $length; //$ex;
		unset($length);
    $bb[$k] = print_r($ex,1);//$tr;
  } elseif($ex[0] == 'PRIMARY'){
    // primary keys
    $primary[] = str_replace(array('`', '(', ')'), '', $ex[2]);
  } elseif($ex[0] == 'KEY'){
    $keys[] = $tr;
  } else {
    $notCols[] = $tr;
  }
}
/*echo '
rows = '.print_r($bb,1).'
Not Column = '.print_r($notCol,1).'
Columns = '.print_r($columns,1).'
primary key = '.print_r($primary,1).'
keys = '.print_r($keys,1); */

$aryKeys = array_keys($columns);
$allColumnStr = '`'.implode('`, `', $aryKeys).'`';
$allColumnVar = '$'.implode(', $', $aryKeys);

$insert_arys = '';
foreach($columns as $col => $ary){
	$type = isset($ary['var']) ? 'PARAM_STR' : 'PARAM_INT';
	$insert_ary[] .= '[\''.$col.'\', $'.$col.', \PDO::'.$type.']';
}
echo '

class '.$tbName[1].' {
  
  protected $db;
  public $DBAL;
  
  public function __construct(){
		global $DBAL;
    $this->db = \''.$tbName[1].'\';
    $this->DBAL = $DBAL;
  }
  
  public function add_row('.$allColumnVar.'){
    $new_id = $this->DBAL->insert($this->db,[
      '.implode(",\n      ", $insert_ary).'
    ]);
    return $new_id;
  }
  
  public function update($updateAry, $whereAry, $limit=1){
    foreach($updateAry as $k => $v){
      $set[] = [$k, $v];
    }
    foreach($whereAry as $k => $v){
      $where[] = [$k, $v];
    }
    $updated = $this->DBAL->update($this->db, $set, $where);
    return $updated;
  }
  
  // we remove them by id / primary key whichever that is on the table.
  public function remove($id){
    return $this->DBAL->delete($this->db, [[\'id\', $id, \PDO::PARAM_INT]]);
  }
  
  public function select_id($id){
		$binds[] = [\':id\', $id, \\PDO::PARAM_INT];
    $go = $this->DBAL->select("SELECT '.$allColumnStr.' FROM ".$this->db." WHERE id = :id LIMIT 1", $binds);
    return $go;
  }
  
  public function browse($pg, $tpp, $whereAry, $order=\'\'){
    $pg = $pg > 0 ? $pg : 1;
    $offset = ($pg-1)*$tpp;
    if(is_array($whereAry)){
      foreach($whereAry as $k => $v){
        $where[] = \'`\'.$k.\'` = :\'.$k.\'\';
        $binds[] = [\':$k\', $k];
      }
      if(isset($where)){
        $where = \' WHERE \'.implode(" AND ", $where);
      } else {
        $where = \'\';
      }
    } else {
			$binds = \'\';
      $where = \'\';
    }
    if($order != \'\'){
      $order = \' ORDER BY \'.$order.\'\';
    }
    $go = $this->DBAL->select("SELECT '.$allColumnStr.' 
      FROM ".$this->db." 
      ".$where.$order."
      LIMIT ".$offset.", ".$tpp, 10, $binds);
    return $go;
  }
}';

?>