#!/bin/bash

SRC_DIR=$(dirname $0)
source $SRC_DIR/utils.sh

VERSION=$1
APPLICATION_ENV=$2

#exit the script if the version is not defined
if [ -z "$VERSION" ]
  then
    echo "No Git version supplied! eg: deploy.sh master staging"
    exit 1
fi

#initialize the environment
if [ -z "$APPLICATION_ENV" ]; then
  APPLICATION_ENV=prod
elif [ "$APPLICATION_ENV" != "prod" ] && [ "$ENV" ]; then
  APPLICATION_ENV=$ENV
fi

echo "Using environment $APPLICATION_ENV"

if [ $APPLICATION_ENV == "prod" ]; then
  if [ "$VERSION" != "" ]; then
    deploy_branch 
  elif [[ "$VERSION" == v* ]]; then
    deploy_tag
  else
    echo "Invalid version number. In production you can deploy a tag or master branch"
    exit
  fi

  python ./bin/build.py --env=prod

  chown -R www-data:www-data bin config data module vendor public
  chmod -R 755 bin config data module vendor public
  chmod -R 777 data/cache data/logs data/log
  echo "Deploy finished"  
  
elif [ $APPLICATION_ENV == "staging" ]; then   
  deploy_branch

  python ./bin/build.py --env=$APPLICATION_ENV

  chown -R www-data:www-data *
  chmod -R 755 *
  chmod -R 777 data/cache data/logs data/log
else
    echo "Environment is not allowed"
fi

echo "done!"
exit 0

