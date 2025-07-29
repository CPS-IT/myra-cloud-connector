# Command / CLI

The Command is based on a Symfony Command and is only accessible via CLI (not via Schedule)

### Command
```shell
vendor/bin/typo3 myracloud:clear
```

### Usage
```shell
  myracloud:clear [options]
  myracloud:clear -t page -i [PAGE_UID like: 123]
  myracloud:clear -t resource -i [PATH like: /fileadmin/path/To/Directory]
  myracloud:clear -t resource -i [PATH like: /assets/myCustomAssets/myScript.js]
  myracloud:clear -t resource -i [PATH like: /fileadmin/path/ToFile.jpg]
  myracloud:clear -t all
  myracloud:clear -t allresources
```

### types
`-t, --type=TYPE`

* page
* resource
* all
* allresources

#### --type=page

`page` type require an page identifier (pid) `-i 1`.
the pid must be numeric.

`page` clear commands are never recursive

#### --type=resource

`resource` type require an uri identifier `-i /path/to/something`.

`resource` clear commands are always recursive

The `resource` type can also be used to clear pages. Simply Provide the site uri.
(this will be recursive)

#### --type=allresources

`allresources` type require NO extra option.

clear everything, recursive, under these folders :
* /fileadmin/*
* /typo3/*
* /typo3temp/*
* /typo3conf/*

`allresources` clear commands are always recursive

#### --type=all

`all` type require NO extra option.

clear everything in myracloud for this TYPO3 Instance.

`all` clear commands are always recursive
