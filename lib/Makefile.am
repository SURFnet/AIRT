# AIR: APPLICATIION FOR INCIDENT RESPONSE
# Copyright (C) 2004	Kees Leune <kees@uvt.nl>
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
# $Id$

# 
SUBDIRS = . import_filters export_wrappers

scriptdir=@AIRT_PHPLIBDIR@

script_DATA = airt.plib authentication.plib constituency.plib database.plib \
	export.plib history.plib incident.plib mailtemplates.plib search.plib \
	user.plib error.plib network.plib importqueue.plib exportqueue.plib \
	server.plib profiler.plib mailbox.plib login.plib setup.plib \
	config.plib formkey.plib

EXTRA_DIST = $(script_DATA)
