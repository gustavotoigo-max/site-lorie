#!/usr/bin/env bash
# Gera os pacotes instaláveis pelo painel do WordPress (Plugins/Temas › Enviar).
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/lorie-core.zip dist/lorie-tema.zip
(cd wp-content/plugins && zip -rq ../../dist/lorie-core.zip lorie-core -x '*.DS_Store')
(cd wp-content/themes && zip -rq ../../dist/lorie-tema.zip lorie -x '*.DS_Store')
ls -lh dist
