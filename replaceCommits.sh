#!/bin/bash

# Git Author Rewrite Script
# Ersetzt alle Commits mit neuen Author-Daten ohne Strukturänderung

# Konfiguration - Passe diese Werte an:
OLD_EMAIL="m.harder@bsg.de"
OLD_NAME="Maxim Harder"
NEW_EMAIL="info@maxim-harder.de"
NEW_NAME="Maxim Harder"

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Git Author Rewrite Script${NC}"
echo "=================================="

# Sicherheitscheck
echo -e "${RED}WARNUNG: Dieses Script ändert die Git-Historie!${NC}"
echo "Erstelle ein Backup deines Repositories bevor du fortfährst."
echo
read -p "Möchtest du fortfahren? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Abgebrochen."
    exit 1
fi

# Repository Status prüfen
if [ ! -d ".git" ]; then
    echo -e "${RED}Fehler: Kein Git Repository gefunden!${NC}"
    exit 1
fi

# Uncommitted Changes prüfen
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}Fehler: Du hast uncommitted Changes!${NC}"
    echo "Committe oder stashe deine Änderungen zuerst."
    exit 1
fi

echo -e "${GREEN}Repository Status: OK${NC}"
echo

# Author-Daten anzeigen
echo "Aktuelle Konfiguration:"
echo "OLD_EMAIL: $OLD_EMAIL"
echo "OLD_NAME: $OLD_NAME"
echo "NEW_EMAIL: $NEW_EMAIL"
echo "NEW_NAME: $NEW_NAME"
echo

# Git filter-branch ausführen
echo -e "${YELLOW}Starte Author-Rewrite...${NC}"

git filter-branch --env-filter '
if [ "$GIT_COMMITTER_EMAIL" = "'$OLD_EMAIL'" ] || [ "$GIT_AUTHOR_EMAIL" = "'$OLD_EMAIL'" ] || [ "$GIT_COMMITTER_NAME" = "'$OLD_NAME'" ] || [ "$GIT_AUTHOR_NAME" = "'$OLD_NAME'" ]; then
    export GIT_COMMITTER_NAME="'$NEW_NAME'"
    export GIT_COMMITTER_EMAIL="'$NEW_EMAIL'"
    export GIT_AUTHOR_NAME="'$NEW_NAME'"
    export GIT_AUTHOR_EMAIL="'$NEW_EMAIL'"
fi
' --tag-name-filter cat -- --branches --tags

# Cleanup
echo -e "${YELLOW}Cleanup...${NC}"
git for-each-ref --format='delete %(refname)' refs/original | git update-ref --stdin
git reflog expire --expire=now --all
git gc --prune=now

echo -e "${GREEN}Author-Rewrite abgeschlossen!${NC}"
echo
echo "Nächste Schritte:"
echo "1. Prüfe die Änderungen mit: git log --oneline"
echo "2. Für Remote Push: git push --force-with-lease origin --all"
echo "3. Tags pushen: git push --force-with-lease origin --tags"