<?php

KService::get('com://site/taxonomy.aliases')->setAliases();

echo KService::get('com://site/taxonomy.dispatcher')->dispatch();