#!/bin/zsh
zparseopts -A opts -name: -user: -email: -version: -notes:
if [[ $# -lt 10 ]]; then
  echo "Usage ${0} --name NAME --user USER --email EMAIL --version VERSION --notes NOTES"
  exit 1
fi
PACKAGE='awscrt'
NAME="${opts[--name]}"
USER="${opts[--user]}"
EMAIL="${opts[--email]}"
VERSION="${opts[--version]}"
NOTES="${opts[--notes]}"

./prepare_package_xml.sh --name "${NAME}" --user "${USER}" --email "${EMAIL}" --version "${VERSION}" --notes "${NOTES}" >package.xml
if [[ $? -ne 0 ]]; then
  echo "ERROR PROCESSING review package.xml"
  exit 1
fi
tidy -xml -m -i package.xml
pear package-validate
if [[ $? -ne 0 ]]; then
  echo "ERROR VALIDATING review package.xml"
  exit 1
fi
pear package
if [[ $? -ne 0 ]]; then
  echo "ERROR PROCESSING review package.xml"
  exit 1
fi

echo "Size of ${PACKAGE}-${VERSION}.tgz: " $(du -h "${PACKAGE}-${VERSION}.tgz")
