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
my $TicketID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);

my ($OwnerID, $Owner) = $TicketObject->OwnerCheck(TicketID => $TicketID);

$TicketObject->StateSet(
   StateID  => 2,
   TicketID => $TicketID,
   UserID   => $OwnerID
);

print "Attempted close.";

exit;
