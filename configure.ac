# configure.in for liberty
# AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
# Copyright (C) 2004    Kees Leune <kees@uvt.nl>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
# $Id$
m4_include([VERSION.m4])dnl

AC_INIT([airt], [AIRT_VERSION], [kees@leune.org])

AC_PREREQ(2.59)
AC_COPYRIGHT([
Copyright (C) 2004 Tilburg University, The Netherlands
])

AC_DEFINE_DIR(ETCPATH, sysconfdir/airt)
AC_DEFINE_DIR(PHPPATH, datadir/@PACKAGE@/php)
AC_DEFINE_DIR(PHPLIBPATH, datadir/@PACKAGE@/lib)
AC_DEFINE_DIR(DOCPATH, datadir/doc/@PACKAGE@)
AC_DEFINE_DIR(STATEPATH, localstatedir/@PACKAGE@)

AM_INIT_AUTOMAKE()

AC_OUTPUT([Makefile
    README
    doc/Makefile
    doc/examples/Makefile
    doc/database/Makefile
    lib/Makefile
    php/Makefile
	php/login.php
    etc/Makefile
    etc/airt-apache.conf
    etc/airt.cfg
    man/Makefile
    php/config.plib
    man/airt.pod
])
