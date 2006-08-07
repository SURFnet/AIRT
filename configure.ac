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

AC_INIT([airt], [AIRT_VERSION], [airt-dev@uvt.nl])

AC_PREREQ(2.59)
AC_COPYRIGHT([
Copyright (C) 2005 Tilburg University, The Netherlands
])

AM_INIT_AUTOMAKE()

AC_DEFINE_DIR(ETCDIR, sysconfdir/airt)


AC_ARG_WITH(phpdir,
    AC_HELP_STRING(--with-phpdir=DIR,Location of PHP files that may be accessed by the web server [datadir/airt/php]), 
    AIRT_PHPDIR="$withval")
if test -z "$AIRT_PHPDIR"
then
    if test "x$prefix" = xNONE
    then
        AIRT_PHPDIR="$ac_default_prefix/share/airt/php"
    else
        AIRT_PHPDIR="$prefix/share/airt/php"
    fi
fi
AC_SUBST(AIRT_PHPDIR)

AC_ARG_WITH(phplibdir,
    AC_HELP_STRING(--with-phplibdir=DIR,Location of PHP files that may not be accessed by the web server [datadir/airt/lib]), 
    AIRT_PHPLIBDIR="$withval")
if test -z "$AIRT_PHPLIBDIR"
then
    if test "x$prefix" = xNONE
    then
        AIRT_PHPLIBDIR="$ac_default_prefix/share/airt/lib"
    else
        AIRT_PHPLIBDIR="$prefix/share/airt/lib"
    fi
fi
AC_SUBST(AIRT_PHPLIBDIR)

AC_ARG_WITH(docdir,
    AC_HELP_STRING(--with-docdir=DIR,Location of AIRT documentation [datadir/doc/airt]),
    AIRT_DOCDIR="$withval")
if test -z "$AIRT_DOCDIR"
then
    if test "x$prefix" = xNONE
    then
        AIRT_DOCDIR="$ac_default_prefix/share/doc/airt"
    else
        AIRT_DOCDIR="$prefix/share/doc/airt"
    fi
fi
AC_SUBST(AIRT_DOCDIR)

AC_OUTPUT([Makefile
    README
    bin/Makefile
    bin/airt_import
    bin/airt_export
    doc/Makefile
    doc/examples/Makefile
    doc/database/Makefile
    doc/database/airtbootstrap.sql
    man/Makefile
    man/airt.pod
    man/airt_import.pod
    man/airt_export.pod
    etc/Makefile
    etc/airt-apache.conf
    etc/airt.cfg
    lib/Makefile
    lib/import_filters/Makefile
    lib/export_wrappers/Makefile
    php/Makefile
    php/login.php
    php/config.plib
])

