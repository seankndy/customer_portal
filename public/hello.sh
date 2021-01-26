#!/bin/bash
BLUE='\033[1;34m'
NC='\033[0m'
PURPLE='\033[1;35m'
RED='\033[1;31m'

bold=$(tput bold)
normal=$(tput sgr0)

clear

echo -e '----------------------------------------------'
echo -e "Welcome to your ${BLUE}Sonar${NC} development environment"
echo -e "To switch panes, press ${bold}CTRL+B${normal} followed by a cursor key to switch to the next window in that direction. For example, press ${bold}CTRL+B ↓${normal} to go to the next pane down."
echo -e "See ${BLUE}https://tmuxcheatsheet.com${NC} for more shortcuts!"
echo -e "${PURPLE}glhf${NC}, but don't forget.. ${BLUE}george${NC} is watching 👁👃👁️ ʕ ͡° ͜ʖ ͡°ʔ"
echo -e '----------------------------------------------'
cat <<EOF
                        __
                       /  ;
                   _.--"""-..   _.
                  /F         '-'  [
( shabalagooo )> ]  ,    ,    ,    ;
                  '--L__J_.-"" ',_;
                      '-._J
EOF
echo -e "${PURPLE}ghoti${NC} sez ${RED}shabalagooo${NC}"
echo -e '----------------------------------------------'
echo -e "🧠 Try typing ${bold}${BLUE}sonar-cli${NC}${normal} for some helpful commands! 🏴󠁧󠁢󠁷󠁬󠁳󠁿"
echo -e '----------------------------------------------'
echo -e "By the way, if you're trying to figure out how to scroll, CTRL+b, then [, then you can use your arrows. Press q to quit scrolling mode."