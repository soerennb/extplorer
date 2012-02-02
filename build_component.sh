#!/bin/bash
# ----------------------------------------------------------------------------
#
# Component Install Archive Builder
#
# This file is part of eXtplorer
#
# ----------------------------------------------------------------------------

# YOU MUST HAVE INSTALLED THE 4.x BETA VERSION OF p7zip (the command line version of 7-Zip for Unix/Linux).
# It's usually globally accessible (in the directory /usr/local/bin/)

DATE=$(date +%Y%m%d)
PATH='/home/soeren/Joomla/components/extplorer'
cd $PATH
SEVENZIP='/usr/bin/7za'
$SEVENZIP a -ttar -r $PATH/scripts.tar scripts
$SEVENZIP d -r $PATH/scripts.tar .svn/
$SEVENZIP a -tgzip $PATH/scripts.tar.gz $PATH/scripts.tar
/bin/rm $PATH/scripts.tar

$SEVENZIP a -tzip -r $PATH/com_extplorer.zip
$SEVENZIP d -r $PATH/com_extplorer.zip .svn/
$SEVENZIP d $PATH/com_extplorer.zip archive/ scripts/

$SEVENZIP d -r $PATH/com_extplorer.zip build_component.sh build_component.bat .project .projectOptions .cache preinstall.php README_PREINSTALL.txt

/bin/rm $PATH/scripts.tar.gz