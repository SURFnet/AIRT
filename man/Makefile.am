# $Id$

man_MANS = airt.1

CLEANFILES = airt.1 airt.pod
DISTCLEANFILES = $(CLEANFILES)

EXTRA_DIST = airt.pod.in airt.pod airt.1

airt.1: airt.pod
	pod2man --release="$(PACKAGE) $(VERSION)" --center=airt $< $@
