<?php
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/setup.plib';
require_once LIBDIR.'/config.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action'));
defaultTo($action, 'list');

Setup::getOption('baseurl', $url, true);

switch ($action) {
   case 'list':
      showConfigScreen();
      break;
   case 'save':
      saveConfig();
      reload($url.'/config.php');
      break;
   default:
      airt_msg(_('Unknown request'));
      reload($url.'/config.php');
}
