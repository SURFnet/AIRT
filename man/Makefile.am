# $Id$

man_MANS = airt.1 airt_import.1

CLEANFILES = airt.1 airt.pod airt_import.1 airt_import.pod
DISTCLEANFILES = $(CLEANFILES)

EXTRA_DIST = airt.pod.in airt.pod airt.1 airt_import.pod.in airt_import.pod airt_import.1

airt.1: airt.pod
	pod2man --release="$(PACKAGE) $(VERSION)" --center=airt $< $@

airt_import.1: airt_import.pod
	pod2man --release="$(PACKAGE) $(VERSION)" --center=airt_import $< $@
