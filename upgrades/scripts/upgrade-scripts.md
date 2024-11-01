## A note about Upgrade Scripts

These are run when the plugin is upgraded from a lower version to the specified version. Note that the framework around upgrades does go through all revisions that we have scripts for (in SemVer order) so you can be sure that all scripts will be run from the initial installed version to the version being upgrade to.

## Instructions for Upgrade Scripts

1. Create a file in scripts directory. The name is the version with '.' replaced with '-'s. (e.g. 1.4.4 upgrade instructions are in 1-4-4.php).
2. Put the upgrade instruction set (the php code) into this file to run when this version. This code will only be run after the upgrade happens the first time.
2. Update function `tot_upgrade_steps` to include this point release in detection.php.
3. Update `tot_upgrade_steps` in scripts/1-4-4.php


### tot_upgrade_steps

New step versions are added to the returned array.

```javascript 1.8
function tot_upgrade_steps() {
	return array(
		'1.4.4', '1.5.0'
	);
}
```