<?php
/* $Id$
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Tilburg University, The Netherlands

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
$public=1;
require_once '/etc/airt/airt.cfg';
require_once LIBDIR.'/authentication.plib';
require_once LIBDIR.'/airt.plib';

$crt = openssl_x509_parse(openssl_x509_read($_SERVER['SSL_CLIENT_CERT']));

/* get some stuff from the certificate; dont forget to convert email address
 * to all lower case characters!
 */
$email = strtolower($crt['subject']['emailAddress']);
$issuerCA = $crt['issuer']['CN'];
$validfrom = $crt['validFrom_time_t'];
$validto = $crt['validTo_time_t'];
$now = time();

/* check validity and CA */
if ($now < $validfrom || $now > $validto || $issuerCA != 'UvT-CA') {
	pageHeader("Invalid credentials");
	printf("Invalid certificate");
	airt_invalidCredentials();
	exit();
}

/* do we know the user? */
$user = getUserByEmail($email);
if (!$user) {
	pageHeader("Invalid credentials");
	printf("Unknown email address");
	airt_invalidCredentials();
	exit();
}

/* init! */
airt_initSession($user['id']);
?>
