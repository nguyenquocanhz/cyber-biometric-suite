#!/usr/bin/env bash
# ==============================================================================
# CYBER MEDIA STREAMER - ONE-CLICK BASH LAUNCHER FOR LINUX CLI
# Cấu hình thực thi siêu tốc: `./ani.sh "Tên Phim"`
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if command -v ruby >/dev/null 2>&1; then
    exec ruby "$SCRIPT_DIR/ani_cli.rb" "$@"
elif command -v python3 >/dev/null 2>&1; then
    exec python3 "$SCRIPT_DIR/stream_cli.py" "$@"
else
    echo -e "\e[91m❌ Lỗi: Cần cài đặt Ruby hoặc Python3 trên hệ thống để chạy Ani-CLI! \e[0m"
    echo -e "\e[93m👉 Cài đặt nhanh: sudo apt install ruby mpv -y \e[0m"
    exit 1
fi
