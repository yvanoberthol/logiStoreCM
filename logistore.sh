#!/bin/bash
sudo php bin/console d:da:c
sudo php bin/console d:m:di
sudo php bin/console d:m:m
sudo php bin/console cache:clear
sudo mkdir db && sudo chmod -R 777 db
sudo mkdir files && sudo chmod -R 777 files
sudo mkdir migrations && sudo chmod -R 777 migrations
sudo mkdir var/cache
sudo mkdir var/log && sudo chmod -R 777 var


