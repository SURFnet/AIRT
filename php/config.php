<?php
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/setup.plib';
require_once LIBDIR.'/config.plib';

$request = strip_tags(fetchFrom('REQUEST', 'action'));
defaultTo($action, 'list');

switch ($action) {
   case 'list':
      showConfigScreen();
      break;
   default:
      Setup::getConfig('baseurl', $url, true);
      reload($url);
}
?>
