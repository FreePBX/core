#!/usr/bin/perl -w

# Small program to process a tiff file into a PDF and email it. 
#
# Distributed under the terms of the GNU General Public License (GPL) Version 2
# Copyright 2005 by Rob Thomas (xrobau@gmail.com)

use MIME::Base64;
use Net::SMTP;

# Default paramaters
my $to = "xrobau\@gmail.com";
my $from = "fax\@";
my $subject = "Fax received";
my $ct = "application/x-pdf";
my $file = undef;
my $attachment = undef;

# Care about the hostname.
my $hostname = `/bin/hostname`;
chomp ($hostname);
if ($hostname =~ /localhost/) {
	$hostname = "set.your.hostname.com";
}
$from .= $hostname;

# Usage:
my $usage="Usage: --file filename [--attachment filename] [--to email_address] [--from email_address] [--type content/type] [--subject \"Subject Of Email\"]"; 

# Parse command line..
while (my $cmd = shift @ARGV) {
  chomp $cmd;
  # My kingdom for a 'switch'
  if ($cmd eq "--to") {
	my $tmp = shift @ARGV;
	$to = $tmp if (defined $tmp);
  } elsif ($cmd eq "--subject") {
	my $tmp = shift @ARGV;
	if ($tmp =~ /\^(\")|^(\')/) {
		# It's a quoted string
		my $delim = $+;   # $+ is 'last match', which is ' or "
		$tmp =~ s/\Q$delim\E//; # Strip out ' or "
		$subject = $tmp;
		while ($tmp = shift @ARGV) {
			if ($tmp =~ /\Q$delim\E/) {
				$tmp =~ s/\Q$delim\E//;
				last;
			}
		$subject .= $tmp;
		}
	} else {
		# It's a single word
		$subject = $tmp;
	}
  # Convert %20 to spaces, leave anythign else alone.
  $subject =~ s/\%20/ /g;
  } elsif ($cmd eq "--type") {
	my $tmp = shift @ARGV;
	$ct = $tmp if (defined $tmp);
  } elsif ($cmd eq "--from") {
	my $tmp = shift @ARGV;
	$from = $tmp if (defined $tmp);
  } elsif ($cmd eq "--file") {
	my $tmp = shift @ARGV;
	$file = $tmp if (defined $tmp);
  } elsif ($cmd eq "--attachment") {
	my $tmp = shift @ARGV;
	$attachment = $tmp if (defined $tmp);
  } else {
	die "$cmd not understood\n$usage\n";
  }

}

# OK. All our variables are set up.
# Lets make sure that we know about a file...
die $usage unless $file;
# and that the file exists...
open( FILE, $file ) or die "Error opening $file: $!"; 
# Oh, did we possibly not specify an attachment name?
$attachment = $file unless ($attachment);

my $encoded="";
my $buf="";
# First, lets find out if it's a TIFF file
read(FILE, $buf, 4);
if ($buf eq "MM\x00\x2a" || $buf eq "II\x2a\x00") {
	# Tiff magic - We need to convert it to pdf first
	# Need to do some error testing here - what happens if tiff2pdf
	# doesn't exist?
	open PDF, "tiff2pdf $file|";
	$buf = "";
	while (read(PDF, $buf, 60*57))  {
  		$encoded .= encode_base64($buf);
	}
	close PDF;
} else {
	# It's a PDF already
	# Go back to the start of the file, and start again
	seek(FILE, 0, 0); 
	while (read(FILE, $buf, 60*57)) {
		$encoded .= encode_base64($buf);
	}
}
close FILE;

# Now we have the file, we should ensure that there's no paths on the
# filename..
$attachment =~ s/^.+\///;

# And that's pretty much all the hard work done. Now we just create the
# headers for the MIME encapsulation: 
my $boundary = '------FREEPBX_FAX_MAIL:'; 
my $dtime = `date`;
chomp $dtime;
my @chrs = ('0' .. '9', 'A' .. 'Z', 'a' .. 'z'); 
foreach (0..16) { $boundary .= $chrs[rand (scalar @chrs)]; } 

my $len = length $encoded;
# message body..
my $msg ="Content-Class: urn:content-classes:message
Content-Transfer-Encoding: 7bit
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=\"$boundary\"
From: $from
Date: $dtime
Reply-To: $from
X-Mailer: dofaxmail.pl
To: $to
Subject: $subject

This is a multi-part message in MIME format.

--$boundary 
Content-Type: text/plain; charset=\"us-ascii\"
Content-Transfer-Encoding: quoted-printable

A Fax has been recieved by the fax gateway, and is attached to this message.


--$boundary
Content-Type: $ct; name=\"$attachment\"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename=\"$attachment\"

$encoded 
--$boundary-- 
";

#print "$msg";
# Now we just send it.
my $smtp = Net::SMTP-> new("127.0.0.1", Debug => 0) or
  die "Net::SMTP::new: $!";
$smtp-> mail($from);
$smtp-> recipient($to);
$smtp-> data();
$smtp-> datasend($msg);
$smtp-> dataend();

