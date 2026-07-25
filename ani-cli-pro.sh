#!/usr/bin/env bash
# ==============================================================================
# ANI-CLI PRO - LINUX BASH LAUNCHER (KKPHIM VIETSUB + GOGOANIME)
# Cấu hình thực thi: `./ani-cli-pro.sh "Tên Phim"`
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if command -v ruby >/dev/null 2>&1; then
    exec ruby "$SCRIPT_DIR/ani_cli.rb" "$@"
elif command -v python3 >/dev/null 2>&1; then
    exec python3 "$SCRIPT_DIR/stream_cli.py" "$@"
else
    echo -e "\e[91m❌ Lỗi: Cần cài đặt Ruby hoặc Python3 trên hệ thống! \e[0m"
    echo -e "\e[93m👉 Cài đặt nhanh: sudo apt install ruby mpv -y \e[0m"
    exit 1
fi
