<?php
/*
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004-2005	Tilburg University, The Netherlands

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 * 
 * NOTE: this module depends on the Pear module XML_RSS, which in turn depends
 * on XML_Tree.
 */
require_once 'XML/RSS.php';

$rssfeeds = array(
	'sans'  => 'http://images.dshield.org/rssfeed.xml',
	'sophos' => 'http://www.sophos.com/virusinfo/infofeed/tenalerts.xml'
);

foreach ($rssfeeds as $feed) {
	$rss =& new XML_Rss($feed);
	$rss->parse();

	$channelinfo = $rss->getChannelInfo();
	printf("<h2 class='feed'><a href='%s'>%s</a></h2>\n",
		$channelinfo['link'], 
		$channelinfo['title']);
	printf("<ul class='item'>\n");
	foreach ($rss->getItems() as $item) 
		printf("<li><a href='%s'>%s</a>\n",
			$item['link'],
			$item['title']);
	printf("</ul>\n");
}
	
?>
