#!/bin/bash

# Note : 
# Debug : bash -x nomscript.sh

directory='application_modeling'
composer='composer.json'

# Store software directory
softwareDirectory=`pwd`




# Get install directory
cd $HOME

if [ ! -d $directory ]
then
	mkdir $directory
fi
cd $directory


# Get repository
echo -e "\n"
echo "################## REPOSITORY ##################"
read -p "URL of the repository to model : " url

repo=${url##*/}
repo=${repo%.git}

echo -e "\n`pwd`\n"

if [ ! -d $repo ]
then
	echo "Cloning repository..."
	git clone $url > /dev/null 2>&1
	echo -e "Done\n"
	cd $repo
else 
	cd $repo
	echo "Getting last version..." 
	git pull origin master > /dev/null 2>&1
	echo -e "Done\n"
fi


# Install dependencies
if [ ! -d $composer ]
then
	echo "Installing dependencies..."
	composer install > /dev/null 2>&1
	echo -e "Done\n"
fi

pathToRepo=`pwd`



# Get Iteration dates
echo "########## BEGINNING OF THE ITERATION ##########"
read -p "Date AAAA-MM-DD : " -n 10 beginDate
echo -e "\n"
read -p "Hour HH:MM : " -n 5 beginHour
echo -e "\n\n"
echo -e "$date, $hour\n"

echo "############# END OF THE ITERATION #############"
read -p "Date AAAA-MM-DD : " -n 10 endDate
echo -e "\n"
read -p "Hour HH:MM : " -n 5 endHour
echo -e "\n\n"
echo -e "$date, $hour\n"




echo -e "\n"
echo "ok"
