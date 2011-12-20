# AIR: APPLICATION FOR INCIDENT RESPONSE
# Copyright (C) 2004,2005	Tilburg University <airt-dev@uvt.nl>
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
#
# $Id: Makefile.am 947 2006-08-07 09:04:35Z kees $

#
scriptdir=@AIRT_PHPLIBDIR@/locale
script_DATA= nl_NL.utf8.mo en_US.utf8.mo
pofiles = en_US.utf8.po nl_NL.utf8.po

install: $(script_DATA)
	for i in $(script_DATA); do\
	  basename=`echo $$i | sed 's|\.mo||g'`;\
	  $(install_sh_DATA) $$i $(scriptdir)/$$basename/LC_MESSAGES/airt.mo;\
		rm -f $(scriptdir)/$$i;\
	done

messages: FORCE
	for i in $(pofiles); do\
	  xgettext --language=PHP --join-existing --no-location --output=$$i \
	  ../php/*.php ../lib/*.plib;\
	done

en_US.utf8.mo: en_US.utf8.po
	msgfmt -o $@ $<

nl_NL.utf8.mo: nl_NL.utf8.po
	msgfmt -o $@ $<

FORCE:

EXTRA_DIST = $(pofiles) $(script_DATA)

CLEANFILES=$(script_DATA)

DISTCLEANFILES=$(CLEANFILES)
