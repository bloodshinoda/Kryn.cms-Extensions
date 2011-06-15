#!/bin/bash

action=$1;
fmod=`pwd`/$2;
mod=$2;
target=$3;

if [ "$action" == "help" ]; then
    echo "Kryn.cms-Extensions install tool";
    echo ;
    echo "This little tool creates symbolic links from the";
    echo "kryn.cms-extensions git repository to a already configured kryn.cms installation";
    echo ;
    echo "usage: $0 install|remove extension installation";
    echo "example: $0 install event_calendar /srv/www/kryn.cms/";
    echo ;
    echo "notice: the current working directory have to be the root of a cloned Kryn.cms-Extensions repository."
    exit;
fi

if [[ "$action" != install && "$action" != remove ]]; then
    echo "argument 1: should be install or remove.";
    exit;
fi

if [ ! -r $mod ]; then
    echo "argument 2: $mod does not exists or isnt readable."
    exit;
fi

if [ ! -r $target ]; then
    echo "argument 3: $target does not exists or isnt readable."
    exit;
fi

if [ "$action" == "install" ]; then
    ln -s $fmod/inc/modules/$mod $target/inc/modules/$mod
    ln -s $fmod/inc/template/$mod $target/inc/template/$mod

    cd $mod;
    for file in $(find . -type f | grep -v modules/$mod/ | grep -v template/$mod/)
    do
        ln -s `pwd`/$file $target/$file;
    done
fi

if [ "$action" == "remove" ]; then
    rm $target/inc/modules/$mod
    rm $target/inc/template/$mod

    cd $mod;
    for file in $(find . -type f | grep -v modules/$mod/ | grep -v template/$mod/)
    do
        rm $target/$file;
    done
fi

echo "done.";
