#!/usr/bin/env bash
# ============================================================
# drfatemeheidari.ir — FTP deploy (cPanel)
# Uploads:
#   public/*  → FTP_PUBLIC_DIR (public_html)
#   app/*     → FTP_APP_DIR    (one level above public_html, private)
#   config.env → FTP_APP_DIR/config.env
#
# Usage:  ./deploy.sh          (reads config.env in this folder)
#         ./deploy.sh public   (only the web root)
#         ./deploy.sh app      (only app/ + config.env)
# Requires: lftp (brew install lftp)
# ============================================================
set -euo pipefail

cd "$(dirname "$0")"

if [[ ! -f config.env ]]; then
  echo "✗ config.env پیدا نشد. از config.env.example کپی کن و مقادیر را پر کن."
  exit 1
fi

# shellcheck disable=SC1091
set -a; source config.env; set +a

: "${FTP_HOST:?FTP_HOST در config.env خالی است}"
: "${FTP_USER:?FTP_USER در config.env خالی است}"
: "${FTP_PASS:?FTP_PASS در config.env خالی است}"

FTP_PORT="${FTP_PORT:-21}"
FTP_SECURE="${FTP_SECURE:-true}"
FTP_PUBLIC_DIR="${FTP_PUBLIC_DIR:-public_html}"
FTP_APP_DIR="${FTP_APP_DIR:-app}"
TARGET="${1:-all}"

# lftp options: TLS + passive + tolerate self-signed on some cPanel hosts
LFTP_OPTS=(set ftp:ssl-allow "$FTP_SECURE"
           set ftp:ssl-force "$FTP_SECURE"
           set ftp:ssl-protect-data "$FTP_SECURE"
           set ftp:passive-mode true
           set ssl:verify-certificate no
           set net:max-retries 3
           set net:timeout 30)

ftp_url() {
  printf 'ftp://%s:%s@%s:%s' "$FTP_USER" "$FTP_PASS" "$FTP_HOST" "$FTP_PORT"
}

run_mirror() {
  local src="$1" dst="$2" extra=("${@:3}")
  lftp "${LFTP_OPTS[@]}" -e "
    set mirror:use-pget-n 1;
    mirror -R --no-perms ${extra[*]} \"$src\" \"$dst\";
    bye" "$(ftp_url)" 2>&1 | grep -v "Transferring" || true
}

echo "→ Deploy target: $TARGET  (host: $FTP_HOST)"

if [[ "$TARGET" == "all" || "$TARGET" == "public" ]]; then
  echo "→ آپلود public/ → /$FTP_PUBLIC_DIR"
  run_mirror "public" "/$FTP_PUBLIC_DIR" --delete
fi

if [[ "$TARGET" == "all" || "$TARGET" == "app" ]]; then
  echo "→ آپلود app/ + config.env → /$FTP_APP_DIR"
  run_mirror "app" "/$FTP_APP_DIR"
  # config.env (single file upload)
  lftp "${LFTP_OPTS[@]}" -e "put config.env -o \"$FTP_APP_DIR/config.env\"; bye" "$(ftp_url)" || true
fi

echo "✓ دیپلوی تمام شد."
echo "  حالا اگر اولین بار است، در مرورگر برو به: https://دامنه/install.php و کلید INSTALL_KEY را وارد کن."
