Summary: Application for Incident Response Teams
Name: airt
Version: 20060810.1
Release: 1
Source0: %{name}-%{version}.tar.gz
Group: Applications/Internet
License: GPL
BuildRoot: /var/tmp/%{name}-buildroot

%description
Computer security incident response teams need to track incidents as they
develop. AIRT is a web-based system to provide incident tracking capabilities
with built-in support for

  * Comprehensive incident management console
  * IP based search of previous incidents
  * Email templates
  * Common AIRT export format for sharing incident data
  * Package-indepedent site configuration so upgrades do not invalidate work


%prep
%setup -q
%build
./configure --prefix=$RPM_BUILD_ROOT/usr --mandir=$RPM_BUILD_ROOT/usr/share/man --sysconfdir=$RPM_BUILD_ROOT/etc
make

%install
make install

%files
%defattr(-,root,root)
/usr/share/airt/php/config.plib
/usr/share/airt/php/constituencies.php
/usr/share/airt/php/constituency_contacts.php
/usr/share/airt/php/export.php
/usr/share/airt/php/exportqueue.php
/usr/share/airt/php/importqueue.php
/usr/share/airt/php/incident.php
/usr/share/airt/php/incident_states.php
/usr/share/airt/php/incident_status.php
/usr/share/airt/php/incident_types.php
/usr/share/airt/php/index.php
/usr/share/airt/php/license.php
/usr/share/airt/php/links.php
/usr/share/airt/php/login.php
/usr/share/airt/php/logout.php
/usr/share/airt/php/mailtemplates.php
/usr/share/airt/php/maintenance.php
/usr/share/airt/php/networks.php
/usr/share/airt/php/search.php
/usr/share/airt/php/server.php
/usr/share/airt/php/stats.php
/usr/share/airt/php/users.php
/usr/share/airt/lib/airt.plib
/usr/share/airt/lib/authentication.plib
/usr/share/airt/lib/constituency.plib
/usr/share/airt/lib/database.plib
/usr/share/airt/lib/error.plib
/usr/share/airt/lib/export.plib
/usr/share/airt/lib/exportqueue.plib
/usr/share/airt/lib/history.plib
/usr/share/airt/lib/importqueue.plib
/usr/share/airt/lib/incident.plib
/usr/share/airt/lib/mailtemplates.plib
/usr/share/airt/lib/network.plib
/usr/share/airt/lib/search.plib
/usr/share/airt/lib/server.plib
/usr/share/airt/lib/user.plib
/usr/share/airt/lib/export_wrappers/wrapper_none
/usr/share/airt/lib/export_wrappers/wrapper_nmap
/usr/share/airt/lib/import_filters/filter_idmef.plib
/usr/share/airt/lib/import_filters/filter_mynetwatchman.plib
/usr/share/airt/lib/import_filters/filter_none.plib
/usr/share/airt/lib/import_filters/filter_spamcop.plib
/usr/share/airt/lib/locale/en_US.utf8/LC_MESSAGES/airt.mo
/usr/share/airt/lib/locale/nl_NL.utf8/LC_MESSAGES/airt.mo
/usr/bin/airt_import
/usr/bin/airt_export

%doc /usr/share/doc/airt/AUTHORS
%doc /usr/share/doc/airt/HOWTO.txt
%doc /usr/share/doc/airt/README
%doc /usr/share/doc/airt/airt_tutorial.pdf
%doc /usr/share/doc/airt/database/00-CHANGES.sql
%doc /usr/share/doc/airt/database/20051108.1.sql
%doc /usr/share/doc/airt/database/20051109.1.sql
%doc /usr/share/doc/airt/database/20051116.1.sql
%doc /usr/share/doc/airt/database/20051123.1.sql
%doc /usr/share/doc/airt/database/20060224.1.sql
%doc /usr/share/doc/airt/database/20060322.1.sql
%doc /usr/share/doc/airt/database/20060324.1.sql
%doc /usr/share/doc/airt/database/20060329.1.sql
%doc /usr/share/doc/airt/database/20060512.1.sql
%doc /usr/share/doc/airt/database/20060515.1.sql
%doc /usr/share/doc/airt/database/20060726.1.sql
%doc /usr/share/doc/airt/database/20060807.1.sql
%doc /usr/share/doc/airt/database/20060810.1.sql
%doc /usr/share/doc/airt/database/airtbootstrap.sql
%doc /usr/share/doc/airt/database/airtschema-from-20050421.1-to-20050607.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20050607.1-to-20050610.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20050610.1-to-20050627.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20050718.1-to-20050726.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20050830.1-to-20050926.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20051010.1-to-20051021.1.sql
%doc /usr/share/doc/airt/database/airtschema-from-20051021.1-to-20050111.1.sql
%doc /usr/share/doc/airt/database/airtschema.sql
%doc /usr/share/doc/airt/database/import_queue.sql
%doc /usr/share/doc/airt/events.txt
%doc /usr/share/doc/airt/examples/certificatelogin.php
%doc /usr/share/doc/airt/exqueue-design.txt
%doc /usr/share/doc/airt/inqueue-design.txt
%doc /usr/share/doc/airt/inqueue-install.txt
%doc /usr/share/doc/airt/session-variables.txt
%doc /usr/share/doc/airt/testing-guide.txt
%doc /usr/share/man/man1/airt.1.gz
%doc /usr/share/man/man1/airt_export.1.gz
%doc /usr/share/man/man1/airt_import.1.gz
%doc /usr/share/doc/airt/CHANGES
%doc /usr/share/doc/airt/ChangeLog



%config /etc/airt/airt-apache.conf
%config /etc/airt/airt.cfg
%config /etc/airt/customfunctions.plib
%config /etc/airt/webservice.cfg
%config /etc/airt/importqueue.cfg
%config /etc/airt/exportqueue.cfg
