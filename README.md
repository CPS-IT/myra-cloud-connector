# Myra Cloud Connector

### Requirements

* php: ^8.2
* ext-json: *
* cpsit/myra-web-api: *
* typo3/cms-core: ^12.4

## Usage

Clear Myra Cloud Remote-Caches out of TYPO3 Backend.

## Basic functionality

The Myra Cloud Clear listen on different event trigger, for example the Myra Cloud-ClearCache-Button im Cache Menu or
the clear Page cache Hook.

for a successful ClearCache, we need at least 3 things,
* Myra Cloud-Config Domain
* fqdn
* resource/uri

the [fqdn's](https://en.wikipedia.org/wiki/Fully_qualified_domain_name) are acquired via Myra Cloud API (DNS-Records) for the given Domain.

after all requirements are loaded the Myra Cloud Cache for every Domain (alias domain), every fqdn (subdomain), every uri are cleared.

### Logging

Every Myra Clear Cache Request will be added into the sys_log database.

## Setup

see: [Settings.md](Docs/Settings.md)

## Page Clear

see: [Settings.md](Docs/Settings.md)

## Filelist Clear

see: [Filelist.md](Docs/Filelist.md)

## Hooks

see: [Hooks.md](Docs/Hooks.md)

## Command / CLI

see: [Command.md](Docs/Command.md)
