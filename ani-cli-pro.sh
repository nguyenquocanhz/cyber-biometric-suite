#!/usr/bin/env bash
# ==============================================================================
# ANI-CLI PRO: LINUX TERMINAL MOVIE & ANIME STREAMER LAUNCHER
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Kiểm tra Python3 & MPV
if ! command -v python3 &> /dev/null; then
    echo -e "\033[91m[LỖI] Chưa cài đặt Python3 trên Linux! Hãy cài đặt bằng lệnh: sudo apt install python3 python3-pip\033[0m"
    exit 1
fi

if ! command -v mpv &> /dev/null; then
    echo -e "\033[93m[CẢNH BÁO] Chưa tìm thấy MPV Media Player! Khuyên dùng MPV bằng cách cài đặt: sudo apt install mpv\033[0m"
fi

python3 "$SCRIPT_DIR/stream_cli.py" "$@"
