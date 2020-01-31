<?php
require_once('steam.class.php');

$appid = 0;

if (!array_key_exists(1, $argv)) {
  die('Укажите id приложения !!!');
}

$page = new Steam($argv[1]);

$res = $page->getItemList(100);

echo count($res);

?>