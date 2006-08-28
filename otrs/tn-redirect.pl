#!/usr/bin/perl -w
#
#

=pod

=head1 Synopsis

Produce a URL that can be used to redirect a browser to a given ticket

=head1 Arguments

The script requires two arguments. 

The first argument is the base url of the OTRS installation.

The second argument is the ticket number to which the URL should point.

=head1 Example

./tn-redirect.pl http://localhost/otrs 2006050910000016

=cut

# use ../../ as lib location
use FindBin qw($Bin);
use lib "$Bin/../..";
use lib "$Bin/../../Kernel/cpan-lib";

use strict;

use Kernel::Config;
use Kernel::System::Time;
use Kernel::System::Log;
use Kernel::System::DB;
use Kernel::System::Ticket;

use vars qw($VERSION @INC);
$VERSION = '$Revision: 1.1 $';
$VERSION =~ s/^\$.*:\W(.*)\W.+?$/$1/;

my $ConfigObject = Kernel::Config->new();
my $TimeObject    = Kernel::System::Time->new(
    ConfigObject => $ConfigObject,
);
my $LogObject    = Kernel::System::Log->new(
    ConfigObject => $ConfigObject,
);
my $DBObject = Kernel::System::DB->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $TicketObject = Kernel::System::Ticket->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
    DBObject => $DBObject,
    TimeObject => $TimeObject,
);

# -------------- En hieronder gebeurt het dus -------------------

my $HOST = shift(@ARGV);
my $TicketNumber=shift(@ARGV);

my $Ticket_ID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);

print "$HOST/index.pl?Action=AgentTicketZoom&TicketID=$Ticket_ID";

exit(0);

