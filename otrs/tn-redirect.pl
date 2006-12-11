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

use Kernel::Config;
use Kernel::System::Time;
use Kernel::System::Log;
use Kernel::System::DB;
use Kernel::System::Ticket;
use Kernel::System::Main;

my $ConfigObject = Kernel::Config->new();
my $LogObject    = Kernel::System::Log->new(
    ConfigObject => $ConfigObject,
);
my $MainObject = Kernel::System::Main->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $TimeObject    = Kernel::System::Time->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $DBObject = Kernel::System::DB->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
);
my $TicketObject = Kernel::System::Ticket->new(
    ConfigObject => $ConfigObject,
    LogObject => $LogObject,
    DBObject => $DBObject,
    MainObject => $MainObject,
    TimeObject => $TimeObject,
);

# -------------- En hieronder gebeurt het dus -------------------

my $HOST = shift(@ARGV);
my $TicketNumber=shift(@ARGV);

print "Using ticket number $TicketNumber\n";
my $TicketID = $TicketObject->TicketIDLookup(
  TicketNumber => $TicketNumber
);
print "Using ticket ID $TicketID\n";

print "$HOST/index.pl?Action=AgentTicketZoom&TicketID=$TicketID";

exit(0);

