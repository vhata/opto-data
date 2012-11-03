#!/bin/bash

cd "`dirname $0`"

patch bootstrap/less/bootstrap.less bootswatch.patch 
cp bootswatch.less variables.less bootstrap/less
cd bootstrap
make bootstrap
mv bootstrap/* ../../static
rmdir bootstrap
rm less/bootswatch.less 
git checkout -- less/bootstrap.less less/variables.less
