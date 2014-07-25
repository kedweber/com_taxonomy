# Taxonomy

## Introduction

Taxomony is used to create hierarchical relations between objects. It is part of the Moyo CCK and is a dependency for
many Moyo components and modules. Although there is no GUI for this component, it contains a number of important helpers
and functions to manage these relations.

`com_taxonomy` is like a roll of duct tape. It holds everything together, even if they weren't meant to. It can relate
articles to each other, tags to articles, articles to categories, categories to categories, articles to events, events
to multimedia items and a lot more.

Com_taxonomy was developed by [Moyo Web Architects](http://moyoweb.nl).

## Requirements

* Joomla 3.X , or possibly Joomla 2.5.
* Koowa 0.9 or 1.0 (as yet, Koowa 2 is not supported)
* PHP 5.3.10 or better
* Composer

## Installation

### Composer

Installation is done through composer. In your `composer.json` file, you should add the following lines to the repositories
section:

```json
{
    "name": "moyo/taxonomy",
    "type": "vcs",
    "url": "https://git.assembla.com/moyo-content.taxonomy.git"
}
```

The require section should contain the following line:

```json
    "moyo/taxonomy": "1.0.*",
```

Afterward, just run `composer update` from the root of your Joomla project.

### jsymlinker

Another option, currently only available for Moyo developers, is by using the jsymlink script from the [Moyo Git
Tools](https://github.com/derjoachim/moyo-git-tools).

## API

### Behaviors - Controller

#### Relationable

This behavior saves a parent identifier and parent type to a taxonomy.

### Behavior - Database

#### Relationable

Does all the work concerning ancestor and descendant management.

#### Node

Each taxonomy is treated as a node in a huge tree. This database behavior contains all node-related functions.

### Orderable

Handles ordering within complex taxonomy structures. Behaves similar to the stock orderable behavior in the `koowa`
framework, and is a complement to the original behavior.