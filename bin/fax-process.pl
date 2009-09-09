#!/usr/bin/perl -w

# Small program to process a tiff file into a PDF and email it. 
#
# This file is part of FreePBX.
#
#    FreePBX is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 2 of the License, or
#    (at your option) any later version.
#
#    FreePBX is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
#
# Copyright 2005 by Rob Thomas (xrobau@gmail.com)

use MIME::Base64;
use Net::SMTP;

# Default paramaters
my $to = "noreply\@mydomain.tld";
my $from = "fax\@";
my $dest = undef;
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
my $usage="Usage: --file filename [--attachment filename] [--to email_address] [--from email_address] [--type content/type] [--subject \"Subject Of Email\"] [--dest DID]"; 

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
  # Convert %2x to proper characters, leave anything else alone.
    $subject =~ s/\%20/ /g;
    $subject =~ s/\%21/\!/g;
    $subject =~ s/\%22/\"/g;
    $subject =~ s/\%23/\#/g;
    $subject =~ s/\%24/\$/g;
    $subject =~ s/\%25/\%/g;
    $subject =~ s/\%26/\&/g;
    $subject =~ s/\%27/\'/g;
    $subject =~ s/\%28/\(/g;
    $subject =~ s/\%29/\)/g;
    $subject =~ s/\%2a/\*/g;
    $subject =~ s/\%2A/\*/g;
    $subject =~ s/\%2b/\+/g;
    $subject =~ s/\%2B/\+/g;
    $subject =~ s/\%2c/\,/g;
    $subject =~ s/\%2C/\,/g;
    $subject =~ s/\%2d/\-/g;
    $subject =~ s/\%2D/\-/g;
    $subject =~ s/\%2e/\./g;
    $subject =~ s/\%2E/\./g;
    $subject =~ s/\%2f/\//g;
    $subject =~ s/\%2F/\//g;
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
  } elsif ($cmd eq "--dest") {
       my $tmp = shift @ARGV;
       if ($tmp =~ /\^(\")|^(\')/) {
               # It's a quoted string
               my $delim = $+;   # $+ is 'last match', which is ' or "
               $tmp =~ s/\Q$delim\E//; # Strip out ' or "
               $dest = $tmp;
               while ($tmp = shift @ARGV) {
                       if ($tmp =~ /\Q$delim\E/) {
                               $tmp =~ s/\Q$delim\E//;
                               last;
                       }
               $dest .= $tmp;
               }
       } else {
               # It's a single word
               $dest = $tmp;
       }
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
my $enc_gif="";
my $buf="";
my $convert_status=0;

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

	open GIF, "convert -resize '50%' -monochrome -delay 300 ${file}[0,1] gif:- |";
	if (!eof(GIF)) {
		$convert_status=1;
		$buf = "";
		while (read(GIF, $buf, 60*57))  {
  			$enc_gif .= encode_base64($buf);
		}
	}
	close GIF;
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
my $dtime = `date -R`;
chomp $dtime;
my @chrs = ('0' .. '9', 'A' .. 'Z', 'a' .. 'z'); 
foreach (0..16) { $boundary .= $chrs[rand (scalar @chrs)]; } 

my $len = length $encoded;
my $len_gif = length $enc_gif;
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

A Fax has been received by the fax gateway and is attached to this message.

The destination number for this fax is ".$dest."


";
if ($convert_status eq 1) {
$msg=$msg."--$boundary
Content-Type: image/gif; name=\"thumb-".substr($attachment,0,-4).".gif\"
Content-Transfer-Encoding: base64

$enc_gif 
";
}

$msg=$msg."--$boundary
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

