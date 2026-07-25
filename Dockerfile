# ==============================================================================
# CYBER BIOMETRIC & MEDIA STREAMER - DOCKER CONTAINER DEFINITION
# Lightweight Python 3.12 + MPV + Ruby Container for Homelab / NAS / Linux
# ==============================================================================

FROM python:3.12-slim

LABEL maintainer="nguyenquocanhz <cuibapvh4@gmail.com>"
LABEL description="Cyber Media Streamer & Biometric Suite Docker Container"

# Cài đặt các gói hệ thống cần thiết (MPV, FFmpeg, Ruby)
RUN apt-get update && apt-get install -y --no-install-recommends \
    mpv \
    ffmpeg \
    ruby \
    git \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Thiết lập thư mục làm việc
WORKDIR /app

# Sao chép toàn bộ mã nguồn vào Container
COPY . /app/

# Tạo thư mục Cache ảnh poster và gán quyền
RUN mkdir -p /app/.cache_images && chmod -R 777 /app/.cache_images

# Khai báo Cổng Web Streamer
EXPOSE 5000

# Biến môi trường Python
ENV PYTHONUNBUFFERED=1

# Lệnh khởi chạy Web Streamer Server chạy nền
CMD ["python3", "web_streamer.py"]
