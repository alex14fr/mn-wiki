#!/bin/sh
DATADIR=${1:-/Sauvegarde_GDR/sauve-persist}
OUTDIR=${2:-/tmp/gdrhtml}
mkdir $OUTDIR
cd $DATADIR/code
sidebar=$(php bin/parse_stdin.php < ../data/pages/sidebar.txt)
logo=$(base64 -w 0 < static/logo.webp)
style="<style>"$(cat static/mstyle.css)"</style>"

for q in ../data/pages/*.txt; do
	p=$(basename $q .txt)
	echo $p
	cat conf/htmlhead.tmpl | grep -v "link rel=stylesheet" | (while read -r l; do 
				[ "$l" == "~~SIDEBAR~~" ] && echo $sidebar ||
					{ (echo $l | grep -q static/logo.webp) && echo "<a href=index.html><img src=data:image/webp;base64,$logo></a>" || echo $l; }; done) | grep -v "~~" > $OUTDIR/$p.html
	( php bin/parse_stdin.php < $q 2>/dev/null ) | sed -e "s/~~[a-zA-Z0-9]*~~//g"| sed -e "s|https://www\.gdr-mascotnum\.fr/||gi"  >> $OUTDIR/$p.html 
	echo $style >> $OUTDIR/$p.html
	cat conf/htmlfoot.tmpl >> $OUTDIR/$p.html
done

