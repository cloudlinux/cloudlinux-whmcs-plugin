#!/usr/bin/env bash
set -e

# yum install -y zip

NAME=whmcs-cl-plugin
SOURCES_DIR=${1:-"./CloudLinux-plugin"}
VERSION=$(grep "'version' =>" $SOURCES_DIR/modules/addons/CloudLinuxAddon/CloudLinuxAddon.php | grep -oP "[\d.]+")

NAME="$NAME-$VERSION"

echo "########## Building WHMCS plugin. ##########"
cd $SOURCES_DIR/frontend
npm install
npm run build:prod
cd ../
zip -rq  ../$NAME.zip ./ -x "frontend/*" -x ".idea/*" -x *.zip -x "e2e-tests/*"
cd -
echo "########## Done zip archive. Find $NAME.zip ##########"

