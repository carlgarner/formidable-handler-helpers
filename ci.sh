#!/bin/bash

for phpfile in `ls -R */*.php`; do
	php -l $phpfile
done
