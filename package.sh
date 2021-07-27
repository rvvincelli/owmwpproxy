#!/bin/sh
# You may use git-archive as an alternative, specifying the ignores in .gitattributes

cd ..
# Leave out the WP codebase to avoid any conflicts.
# Remove and not ignore simply because I could not get the syntax right for the latter.
zip -r owproxy.zip owproxy/; zip --delete owproxy.zip "owproxy/vendor/johnpbloch/*" "owproxy/.git/*" 
cd -
