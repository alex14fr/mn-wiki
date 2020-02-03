#!/usr/bin/perl
$filt=$ARGV[0];
chop $filt;
$buf="";
$imprime=1;
while(<STDIN>) {
	if(/^<link/ || /^<div/ || /^<p/) {
		print;
	}
	elsif(/<\/dl>/) {
		if($imprime) {
			print $buf;
			print;
		}
		$imprime=1;
		$buf="";
	}
	elsif($filt ne '' && /$filt/) {
		$imprime=0;
	}
	else {
		$buf=$buf.$_;
	}
}

