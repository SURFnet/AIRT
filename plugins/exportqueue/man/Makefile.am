# $Id$

man_MANS = airt_export.1

CLEANFILES = airt_export.1 airt_export.pod
DISTCLEANFILES = $(CLEANFILES)

EXTRA_DIST = airt_export.pod.in airt_export.pod airt_export.1

airt_export.1: airt_export.pod
	pod2man --release="$(PACKAGE) $(VERSION)" --center=airt_export $< $@
