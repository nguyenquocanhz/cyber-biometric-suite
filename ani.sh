#!/usr/bin/env bash
# ==============================================================================
# CYBER MEDIA STREAMER - ONE-CLICK BASH LAUNCHER FOR LINUX CLI, GUI, WEB & DOCKER
# Cấu hình thực thi: `./ani.sh "Tên Phim"` hoặc `./ani.sh --docker` hoặc `./ani.sh --web`
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ "$1" == "--docker" || "$1" == "-d" ]]; then
    echo -e "\e[96m🐳 Đang khởi chạy Cyber Web Streamer qua Docker Container chạy nền... \e[0m"
    if command -v docker-compose >/dev/null 2>&1; then
        docker-compose up -d --build
    elif docker compose version >/dev/null 2>&1; then
        docker compose up -d --build
    else
        echo -e "\e[91m❌ Lỗi: Cần cài đặt Docker & Docker Compose trên hệ thống! \e[0m"
        echo -e "\e[93m👉 Cài đặt nhanh: curl -fsSL https://get.docker.com | sh \e[0m"
        exit 1
    fi
    echo -e "\e[92m✅ Container Docker đã chạy ngầm thành công! \e[0m"
    echo -e "\e[96m👉 Truy cập Web Player tại: http://localhost:5000 \e[0m"
elif [[ "$1" == "--web" || "$1" == "-w" ]]; then
    echo -e "\e[96m🌐 Đang khởi chạy Cyber Web Streamer Server (Mở trình duyệt xem phim)... \e[0m"
    exec python3 "$SCRIPT_DIR/web_streamer.py"
elif [[ "$1" == "--gui" || "$1" == "-g" ]]; then
    echo -e "\e[96m⚡ Đang khởi chạy Giao diện đồ họa Cyberpunk Streamer GUI... \e[0m"
    exec python3 "$SCRIPT_DIR/stream_gui.py"
elif command -v ruby >/dev/null 2>&1; then
    exec ruby "$SCRIPT_DIR/ani_cli.rb" "$@"
elif command -v python3 >/dev/null 2>&1; then
    exec python3 "$SCRIPT_DIR/stream_cli.py" "$@"
else
    echo -e "\e[91m❌ Lỗi: Cần cài đặt Ruby hoặc Python3 trên hệ thống! \e[0m"
    echo -e "\e[93m👉 Cài đặt nhanh: sudo apt install ruby python3 mpv -y \e[0m"
    exit 1
fi
