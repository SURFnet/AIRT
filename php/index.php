<?php
/* $Id$ 
 * index.php - Index page for UvT-CERT
 *
 * LIBERTY: INCIDENT RESPONSE SUPPORT FOR END-USERS
 * Copyright (C) 2004	Kees Leune <kees@uvt.nl>

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
 */
  include "../lib/liberty.plib";
  $SELF="index.php";

  if (array_key_exists("action", $_REQUEST))
      $action = $_REQUEST["action"];
  else $action="none";

  switch ($action)
  {
      case "increase":
          require 'lib/getnextincident.plib';
          getNextIncident();
          Header("Location: $SELF");
          break;
      case "none":
          break;
      default:
          die("Unkown action ($action).");
  }

  pageHeader("UvT-CERT Control Center");

  $f = fopen("/var/lib/cert/incident.txt", "r");
  $no = trim(fgets($f));
  fclose ($f);

  echo "<TABLE WIDTH='100%'>";
  echo "<TR>";
  echo "<TD align='left'>";
  printf("Het huidige UvT-CERT incident nummer is UvT-CERT#%05d",
      $no);
  echo "</TD>";
  $f = @fopen("/var/tmp/iscstatus", "r");
  if ($f) 
  {
      echo "<TD align='right'>";
      $level = trim(fgets($f));
      fclose($f);
      echo "<a href=\"http://isc.sans.org/\"><img ".
           "src=\"".BASEURL."/pic/threatlevel_$level.gif\" border=0></a>";
      echo "</TD>";
  }
  echo "</TR>";

  echo "<TR>";
  echo "<TD align='left'>";
  printf("<a href='$SELF?action=increase'>Volgend incident</a>");
  echo "</TD>";

  echo "<TD align='right'>";
  echo "ISC Infocon level: $level";
  echo "</TD>";
  echo "</TR>";

  echo "</TABLE>";
  echo "<HR>";
  echo "<P>";
  $filename=sprintf("/var/lib/cert/last_%s.txt", $_SESSION["username"]);
  if (file_exists($filename))
  {
      $f = fopen($filename, "r");
      $last = fgets($f);
      fclose($f);
      printf("<small>$last</small>");
      echo "<P>&nbsp;<P>";
  }
?>


<a href="search.php">IP Address lookup</a>

<P>

<a href="incident.php">Incident management</a>

<P>

<?php
require 'lib/logins.plib';
session_start();
$user=$_SESSION["username"];
$pass=$USERNAMES[$user];
echo <<<EOF
<a href="https://liberty.uvt.nl/rt/RTIR/index.html?user=$user&pass=$pass">Request tracker</a>
EOF;
?>

<P>

<a href="ports.php">Common TCP/UDP port lookup</a>

<P>

<P>

<a href="contacten_extern.php">Externe contactpunten</a>

<P>

<a href="dienstlijst.php">De dienstlijst</a>

<P>

<a href="contactlijst.php">De UvT-CERT telefoonnummers</a>

<P>
&nbsp;

<P>

<a onclick="return confirm('Are you sure that you want to log out?')" 
   href="logout.php">Logout</a>

<?
    pageFooter();
?>
