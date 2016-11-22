#!/usr/bin/env bash

if [ "$SYMFONY_VERSION" != "" ]; then
    composer require --dev --no-update \
        symfony/http-kernel:$SYMFONY_VERSION \
        symfony/http-foundation:$SYMFONY_VERSION
fi
