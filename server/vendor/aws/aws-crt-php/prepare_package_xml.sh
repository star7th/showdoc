#!/bin/zsh
zparseopts -A opts -name: -user: -email: -version: -notes:
if [[ $# -lt 10 ]]
then
  echo "Usage ${0} --name NAME, --user USER --email EMAIL --version VERSION --notes NOTES"
  exit 1
fi
NAME="${opts[--name]}"
USER="${opts[--user]}"
EMAIL="${opts[--email]}"
VERSION="${opts[--version]}"
NOTES="${opts[--notes]}"

TODAY=$(date -u +%Y-%m-%d)

cat package.xml-template_pre \
    | sed "s/{{{NAME}}}/$NAME/g" \
    | sed "s/{{{USER}}}/$USER/g" \
    | sed "s/{{{EMAIL}}}/$EMAIL/g" \
    | sed "s/{{{TODAY}}}/$TODAY/g" \
    | sed "s/{{{VERSION}}}/$VERSION/g" \
    | sed "s/{{{NOTES}}}/$NOTES/g"

source_ext='(c|cc|h|cpp|hpp|m4|w32|ini|frag|cmake|inl|in|py|gnu|yaml|def|pl|S|s|errordata|go|lds|num|asm|mod|peg|mk|rs|toml|sh)'
doc_ext='(md|json|html|dot|graphml|png|gn|sha1|css|rst|)'

special_docs='(LICENSE*|NOTICE|changelog.txt|CHANGELOG|THIRD-PARTY|README*|readme|METADATA|CONTRIBUTORS|UPDATING|doc.config)'
special_tests='(ci-test.sh|format-check.sh|run_tests*|sanitizer-blacklist.txt|run-clang-tidy.sh|benchmark-build-run.sh|break-tests.sh|generate-coverage.sh|test.xml)'
special_src='(gen_api.php|gen_stub.php|CMakeLists.txt|post.sh|postun.sh|Makefile*|build-buildspec.sh|build-deps.sh|objects.txt|go.*|BUILD*|DEPS|install_and_run.sh|codemod.sh|requirements.txt)'
skip_files='(package.xml*|prepare_release.sh|codereview.settings|*.o|*.a|*.obj|*.lib|break-tests-android.sh|whitespace.txt|prepare_package_xml.sh|crypto_test_data.cc|*.pdf|*.svg|*.docx|cbmc-proof.txt|codecov*|litani*|*.toml)'

special_scripts='(awscrt.stub.php)'

skip_directories='(tests|test|AWSCRTAndroidTestRunner|docker-images|codebuild|fuzz|verfication|third_party|docs|generated-src|aws-lc|aws-crt-sys)'

process_file() {
    if (( $# == 0 ))
    then
      echo "ERROR: filename not passed"
      exit 1
    fi
    if [[ $1 = $~skip_files ]]
    then
      # This file is not part of the release bundle
      return 0
    fi

    echo -n '<file name="'"$1"'" role="'
    # Special cases
    case ${a} in
    $~special_scripts)
      echo -n 'script'
    ;;
    $~special_docs)
      echo -n 'doc'
    ;;
    $~special_tests)
      echo -n 'test'
    ;;
    $~special_src)
      echo -n 'src'
    ;;
    *)
      # Extension based cases
      case ${a:t:e} in
      $~source_ext)
        echo -n 'src'
      ;;
      $~doc_ext)
         echo -n 'doc'
      ;;
      php)
         echo -n 'script'
      ;;
      *)
         echo "${a:t:e} - ${a} - FAIL TO RECOGNIZE"
         exit 1
      esac
    esac
    echo '"/>'
    return 0
}


process_dir() {
  if (( $# == 0 ))
  then
    echo "WARNING: dirname not passed"
    exit 1
  fi
  if [[ "${1}" = $~skip_directories ]]
  then
    return 0
  fi
  echo '<dir name="'"$1"'">'
  cd "$1"
  for a in *
  do
    if [[ -f ${a} ]]
    then process_file "${a}"
    else process_dir "${a}"
    fi
  done
  # Special cases for compiler features placed in tests directories in and s2n
  if [[ "${1}" = "s2n" && -d tests ]]
  then
      echo '<dir name="tests">'
      echo '<dir name="features">'
      cd tests/features
      for a in *
      do
          process_file "${a}"
      done
      cd ../..
      echo '</dir>'
      echo '</dir>'
  fi
  echo '</dir>'
  cd ..
  return 0
}

echo '<dir name="/">'
for a in *
do
  if [[ ${a} == 'tests' ]]
  then
    echo '<dir name="tests">'
    for b in tests/*
    do
      echo '<file name="'$( basename "${b}" )'" role="test" />'
    done
    echo '</dir>'
    continue
  fi
  if [[ -f ${a} ]]
  then process_file "${a}"
  else process_dir "${a}"
  fi
done
echo '</dir>'

cat package.xml-template_post

