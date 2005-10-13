# $Id$

man_MANS = airt_import.1

CLEANFILES = airt_import.1 airt_import.pod
DISTCLEANFILES = $(CLEANFILES)

EXTRA_DIST = airt_import.pod.in airt_import.pod airt_import.1

airt_import.1: airt_import.pod
	pod2man --release="$(PACKAGE) $(VERSION)" --center=airt_import $< $@
