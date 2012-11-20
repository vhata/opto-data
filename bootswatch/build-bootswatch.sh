#!/bin/bash

cd "`dirname $0`"

patch bootstrap/less/bootstrap.less bootswatch.patch 
cp bootswatch.less variables.less bootstrap/less
cd bootstrap
make bootstrap
for z in css js img ; do
	mkdir -p ../../static/$z/
	mv bootstrap/$z/* ../../static/$z/
	rmdir bootstrap/$z/
done
rmdir bootstrap
rm less/bootswatch.less 
git checkout -- less/bootstrap.less less/variables.less
