#!/usr/bin/env bash
# Render definitivi delle hero 3D (circa 3 minuti l'uno su GPU RTX).
# Uso: bash tools/render/render-all.sh [scena ...]
set -e
cd "$(dirname "$0")/../.."
BLENDER="${BLENDER:-/c/Program Files/Blender Foundation/Blender 5.2/blender.exe}"
OUT="$(pwd)/tools/images/src"
declare -A MAP=(
  [home]=hero-home [corsi]=hero-corsi [contatti]=hero-contatti
  [nutrizione-umana]=lens-nutrizione-umana [microbiota]=lens-microbiota [fitoterapia]=lens-fitoterapia
  [nutrizione-animale]=lens-nutrizione-animale [comunicazione]=lens-comunicazione
)
SCENES=("$@")
[ ${#SCENES[@]} -eq 0 ] && SCENES=(home home-tall corsi nutrizione-umana microbiota fitoterapia nutrizione-animale comunicazione contatti)
for s in "${SCENES[@]}"; do
  if [ "$s" = "home-tall" ]; then
    "$BLENDER" -b -P tools/render/lens_world.py -- --scene home --tall --out "$OUT/hero-home-m.png" | grep -E 'RENDER_OK|Error|Traceback' || true
  else
    "$BLENDER" -b -P tools/render/lens_world.py -- --scene "$s" --out "$OUT/${MAP[$s]}.png" | grep -E 'RENDER_OK|Error|Traceback' || true
  fi
done
echo "ALL_DONE"
