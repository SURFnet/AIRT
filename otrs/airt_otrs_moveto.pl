#!/usr/bin/perl -w
#
#=pod
#
#=head1 Synopsis
#
# Move a ticket to a different queue
#
#=head1 Arguments
#
#The script requires one argument.
#
# The first argument is the ticket number that should be moved, the second is
# the name of the queue to move it o
#
#=head1 Example
#
#./ticketclose.pl 2006050910000016
#
#=cut
#

use Kernel::Config;
use Kernel::System::Time;
use Kernel::System::Log;
use Kernel::System::Main;
use Kernel::System::DB;
use Kernel::System::Ticket;

my $ConfigObject = Kernel::Config->new();
my $LogObject    = Kernel::System::Log->new(
    ConfigObject => $ConfigObject,
);
my $MainObject = Kernel::System::Main->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $TimeObject    = Kernel::System::Time->new(
    LogObject => $LogObject,
    ConfigObject => $ConfigObject,
);
my $DBObject = Kernel::System::DB->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $TicketObject = Kernel::System::Ticket->new(
    ConfigObject => $ConfigObject,
    MainObject => $MainObject,
    LogObject => $LogObject,
    DBObject => $DBObject,
    TimeObject => $TimeObject
);

my $TicketNumber=shift(@ARGV);
my $QueueName=shift(@ARGV);
my $TicketID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);

my ($OwnerID, $Owner) = $TicketObject->OwnerCheck(TicketID => $TicketID);

$TicketObject->MoveTicket(
   Queue => $QueueName,
   TicketID => $TicketID,
   UserID   => $OwnerID
);

print "Moved ticket $TicketID to queue $QueueName.\n";

exit;
