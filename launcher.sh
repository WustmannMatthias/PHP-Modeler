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




# Get Iteration parameters
echo "################## ITERATION ##################"
read -p "Reference : " reference
echo -e "\n"
echo -e "ITERATION_NAME = $reference" >> $iterationPath


echo "--> Start"
read -p "Year : " beginYear
read -p "Month : " beginMonth
read -p "Day : " beginDay
read -p "Hour : " beginHour
read -p "Minutes : " beginMinute
echo -e "ITERATION_BEGIN_YEAR 	= $beginYear" >> $iterationPath
echo -e "ITERATION_BEGIN_MONTH 	= $beginMonth" >> $iterationPath
echo -e "ITERATION_BEGIN_DAY 	= $beginDay" >> $iterationPath
echo -e "ITERATION_BEGIN_HOUR 	= $beginHour" >> $iterationPath
echo -e "ITERATION_BEGIN_MINUTE	= $beginMinute" >> $iterationPath
beginDate="$beginYear-$beginMonth-$beginDay $beginHour:$beginMinute"

echo -e "\n"
echo "--> Freeze"
read -p "Year : " endYear
read -p "Month : " endMonth
read -p "Day : " endDay
read -p "Hour : " endHour
read -p "Minutes : " endMinute
echo -e "ITERATION_END_YEAR 	= $endYear" >> $iterationPath
echo -e "ITERATION_END_MONTH 	= $endMonth" >> $iterationPath
echo -e "ITERATION_END_DAY	 	= $endDay" >> $iterationPath
echo -e "ITERATION_END_HOUR 	= $endHour" >> $iterationPath
echo -e "ITERATION_END_MINUTE	= $endMinute" >> $iterationPath
endDate="$endYear-$endMonth-$endDay $endHour:$endMinute"

echo -e "\n\n"


# Ask for confirmation
echo "################# CONFIRMATION #################"
echo -e "Repository : $repo\n"
echo -e "Begin of the iteration : $beginDate\n" 
echo -e "Begin of the iteration : $endDate\n"
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
