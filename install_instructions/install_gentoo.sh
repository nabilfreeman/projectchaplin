#!/bin/bash
emerge apache PEAR-PEAR php pecl-mongo redis
git clone git://github.com/nicolasff/phpredis.git
cd phpredis
phpize
aclocal 
libtoolize --force 
autoheader 
autoconf 
./configure 
make
make install
cd ..

