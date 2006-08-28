#!/usr/bin/perl -w
#
#

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

my $TicketNumber=shift(@ARGV);
my $HOST = 'http://localhost'; # 

my $Ticket_ID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);

print "$HOST/otrs/index.pl?Action=AgentTicketZoom&TicketID=$Ticket_ID";

exit(0);

