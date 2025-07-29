# AutoClear - Hooks

There are 2 Hooks implemented
* Page update
* File Overwrite

Hooks are not affected by [Admin Only UI Elements](Settings.md#Admin Only UI Elements).
Hooks can be disabled via [Disable Hooks Setting](Settings.md#Disable Hooks).

## Page Update

This hook listen on the `DataHadler->clearCachePostProc` interface.

It will only clear a page itself or elements on that page are updated (created/updated/deleted)

Page Clear requests are not recursive.


## File Overwrite

a file via TYPO3 > Filelist to overwrite will trigger this hook.

it loads also all processed files and clear them in MyraCloud.
