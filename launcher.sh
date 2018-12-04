#!/bin/bash

# Note : 
# Debug : bash -x nomscript.sh

directory='application_modeling'
composer='composer.json'
iterationFile='iteration'

# Store software directory
softwareDirectory=`pwd`
iterationPath="$softwareDirectory/$iterationFile"



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
if [ -f $composer ]
then
	echo "Installing dependencies..."
	composer install > /dev/null 2>&1
	echo -e "Done\n"
fi

pathToRepo=`pwd`
echo "REPOSITORY = $pathToRepo" > $iterationPath



# Get Iteration dates
echo "########## BEGINNING OF THE ITERATION ##########"
read -p "Date AAAA-MM-DD : " -n 10 beginDate
echo -e "\n"
read -p "Hour HH:MM : " -n 5 beginHour
echo -e "\n\n"
echo -e "ITERATION_BEGIN = $beginDate, $beginHour" >> $iterationPath

echo "############# END OF THE ITERATION #############"
read -p "Date AAAA-MM-DD : " -n 10 endDate
echo -e "\n"
read -p "Hour HH:MM : " -n 5 endHour
echo -e "\n\n"
echo -e "ITERATION_END = $endDate, $endHour" >> $iterationPath


# Ask for confirmation
echo "################# CONFIRMATION #################"
echo -e "Repository : $repo\n"
echo -e "Begin of the iteration : $beginDate, $beginHour\n" 
echo -e "Begin of the iteration : $endDate, $endHour\n"
read -p "Proceed ? (Y/n)" -n 1 proceed
if [ $proceed != 'y' ] && [ $proceed != 'Y' ]
then 
	echo -e "Script end. \n\n"
	exit 1
fi



# Launch php Script
cd $softwareDirectory

echo -e "\n"
echo -e "Starting...\n"
php index.php





echo -e "\n"
echo "End bash Script"
