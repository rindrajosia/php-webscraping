<?php
Class CSV {
  static function export ($datas, $filename) {
    $dir = getcwd();
    echo $dir;
    @chmod($dir, 0755);
    $fh = fopen($dir.'/coursera.csv', 'w');

    $i = 0;
    foreach($datas as $v){
      if($i == 0){
        fputcsv($fh, array_keys($v), ';');
      }
      fputcsv($fh, array_values($v), ';');
      $i++;
    }
    fclose($fh);
  }
}
