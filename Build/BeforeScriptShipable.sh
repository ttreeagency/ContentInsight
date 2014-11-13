#!/bin/bash

set -e

composer selfupdate --quiet

cat <<EOF > composer.json
{
	"name": "typo3/flow-base-distribution",
	"config": {
		"vendor-dir": "Packages/Libraries",
		"bin-dir": "bin"
	},
	"require": {
		"ttree/contentinsight": "dev-${BRANCH}#${COMMIT}",
		"doctrine/migrations": "dev-master",
		"typo3/flow": "${FLOW_VERSION}"
	},
	"require-dev": {
		"typo3/buildessentials": "${FLOW_VERSION}",
		"mikey179/vfsstream": "1.2.*",
		"phpunit/phpunit": "4.0.*",
		"typo3-ci/typo3flow": "dev-master",
		"flowpack/behat": "dev-master"
	},
	"scripts": {
		"post-update-cmd": "TYPO3\\\\Flow\\\\Composer\\\\InstallerScripts::postUpdateAndInstall",
		"post-install-cmd": "TYPO3\\\\Flow\\\\Composer\\\\InstallerScripts::postUpdateAndInstall",
		"post-package-update":"TYPO3\\\\Flow\\\\Composer\\\\InstallerScripts::postPackageUpdateAndInstall",
		"post-package-install":"TYPO3\\\\Flow\\\\Composer\\\\InstallerScripts::postPackageUpdateAndInstall"
	}
}
EOF

composer install --prefer-source --dev

cat <<EOF > Configuration/Routes.yaml
-
  name: 'Flow'
  uriPattern: '<FlowSubroutes>'
  defaults:
	'@format': 'html'
  subRoutes:
	FlowSubroutes:
	  package: TYPO3.Flow
EOF
