#!/usr/bin/perl -w
#
#=pod
#
#=head1 Synopsis
#
#Close an OTRS ticket by ticket number
#
#=head1 Arguments
#
#The script requires one argument.
#
#The first argument is the ticket number that should be closed.
#
#=head1 Example
#
#./ticketclose.pl 2006050910000016
#
#=cut
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
use Kernel::System::GenericAgent;

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

my $QueueObject = Kernel::System::Queue->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
    DBObject => $DBObject
);

my $TicketObject = Kernel::System::Ticket->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
    DBObject => $DBObject,
    TimeObject => $TimeObject
);

my $GenericAgentObject = Kernel::System::GenericAgent->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
    TimeObject => $TimeObject,
    TicketObject => $TicketObject,
    QueueObject => $QueueObject,
    DBObject => $DBObject
);

my $TicketNumber=shift(@ARGV);
my $Ticket_ID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);

print "allez hop\n\n\n";

$GenericAgentObject->JobRun(
    TicketID => $Ticket_ID,
    TicketNumber => $TicketNumber,
    Job => 'JobName',
    Config => {
                close => {
                           New => {
                                    State => 'closed successful'
                                  }
                         }
              },
    UserID => 2
);

print "Hee jo, check effe in OTRS of ticket $TicketNumber inderdaad succesful geclosed is\n";
print "O ja, nog wat: je moet wel even (opnieuw) inloggen in OTRS\n";


exit;
