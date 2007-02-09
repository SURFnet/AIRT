#!/usr/bin/perl -w
#
#=pod
#
#=head1 Synopsis
#
#Closes every OTRS ticket in a queue
#
#=head1 Arguments
#
#The script requires one argument.
#
#The first argument is the name of the queue that should be cleaned.
#
#=head1 Example
#
#./queuecleaner.pl Raw
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

my $queue = shift;

my @TicketIDs = $TicketObject->TicketSearch(
 Result => 'ARRAY' || 'HASH',
 UserID => 1,
 Queues => [$queue],
 States => ['new','open'],
);

foreach my $TicketID (@TicketIDs)
{
  my ($OwnerID, $Owner) = $TicketObject->OwnerCheck(TicketID => $TicketID);

  $TicketObject->StateSet(
                           StateID  => 2,
                           TicketID => $TicketID,
                           UserID   => $OwnerID
                         );
}


