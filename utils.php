<?php

function san_pageId($id) {
	$id=strtolower($id);
	$id=substr($id,0,47);
	return preg_replace("/[^a-z0-9\-_]/","",$id);
}

function san_pageRev($rev) {
	return preg_replace("/[^0-9]/","",$rev);
}

function san_filename($fn) {
	$fn=basename($fn);
	$fn=strtolower($fn);
	$fn=substr($fn,0,64);
	return preg_replace("/[^a-z0-9\-_\.]/","",$fn);
}
