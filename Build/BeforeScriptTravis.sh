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
		"ttree/contentinsight": "dev-${GIT_BRANCH}#${GIT_COMMIT}",
		"doctrine/migrations": "@dev",
		"typo3/flow": "${FLOW_VERSION}"
	},
	"require-dev": {
		"typo3/buildessentials": "${FLOW_VERSION}",
		"mikey179/vfsstream": "1.4.*",
		"phpunit/phpunit": "4.3.*",
		"typo3-ci/typo3flow": "dev-master"
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
