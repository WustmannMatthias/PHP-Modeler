#!/bin/bash


#Composer install
echo -e "Installing dependencies...\n"
composer install
echo -e "\nDone.\n\n\n"



user=`whoami`




#Data directory install
echo -e "Preparing user directories...\n"

if [ ! -d data ]
then
	mkdir data
fi
cd data

if [ ! -d general_settings ]
then
	mkdir general_settings
fi
touch general_settings/database
touch general_settings/iteration

if [ ! -d projects ]
then
	mkdir projects
fi

if [ ! -d projects_settings ]
then
	mkdir projects_settings
fi

cd ..

echo -e "\nDone.\n\n\n"




#Database settings
echo -e "Parametering database connection...\n"
databaseSettings="data/general_settings/database"

read -p "Database URL : " url
read -p "Database port : " port
read -p "Username : " user
read -s -p "Password : " password
echo -e "DATABASE_URL 	= $url" > $databaseSettings
echo -e "DATABASE_PORT 	= $port" >> $databaseSettings
echo -e "USERNAME	 	= $user" >> $databaseSettings
echo -e "PASSWORD		= $password" >> $databaseSettings
echo -e "\nDone.\n\n\n"






#Managing rights
user=`whoami`

echo -e "Assigning rights to configuration files...\n"
sudo chown -R www-data:www-data data
cd data
sudo chown -R "$user:$user" projects






#Exiting
echo -e "\nApplication installed ! \n"










