#!/bin/bash

deploy_branch () {
    if git show-ref --verify --quiet "refs/heads/$VERSION"; then
        echo "BRANCH EXISTS. Pulling new changes to $VERSION"
        git fetch --all
        git reset --hard origin/$VERSION
    else
        echo "Checking out branch $VERSION"
        git reset --hard
        git fetch
        git checkout -f -b $VERSION origin/$VERSION
    fi
}

deploy_tag () {
    if git show-ref --verify --quiet "refs/heads/$VERSION"; then
        echo "Tag EXISTS. Pulling new changes to $VERSION"
        git fetch --all
        git reset --hard tags/$VERSION
    else
        echo "Checking out tags/$VERSION"
        git reset --hard
        git fetch
        git checkout -f -b $VERSION tags/$VERSION
    fi
}
