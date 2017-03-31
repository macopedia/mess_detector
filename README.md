# TYPO3 Mess Detector

TYPO3 tools that will help you find out how messed up your TYPO3 instance is.
It checks database integrity.

## How to run it
Go to the folder with your TYPO3 installation and run e.g.
```
./typo3/cli_dispatch.phpsh extbase l10nparent:checkuid
```

## Available commands

- `t3origuid:checkt3origuidequalsuid` - Finds all records which has wrong t3_origuid value set

- `l10nparent:checkdefaultlanguage` - Finds all records which has l10n_parent value set to uid of the record in non default language
- `l10nparent:checkpid` - Finds all records which has l10n_parent value set to uid of the record on a different page
- `l10nparent:checkuid` - Finds all records which l10n_parent field value equals uid or are are in default language but have l10nparent set

- `fal:filereferencelanguage` - Finds all sys_file_references which have wrong sys_language_uid (sys_language_uid is different than sys_language_uid of the parent record)
