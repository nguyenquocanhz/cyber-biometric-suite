# ⚡ SHOPKCVIP & CYBER BIOMETRIC SUITE 3.0

> **Hệ thống Tích hợp Nền tảng Web & Bộ Công cụ Giám định Sinh trắc học / Phân loại Khuôn mặt AI / Linux CLI Media Streamer**

---

## 📋 Giới Thiệu Tổng Quan

**ShopKCFF & Cyber Biometric Suite** là một hệ sinh thái phần mềm đa năng được chuẩn hóa kiến trúc bao gồm:
1. **Web Platform**: Cổng dịch vụ web nạp thẻ tự động, tích hợp Cloudflare Turnstile, Telegram Webhook & Cổng thanh toán thẻ cào.
2. **Cyber Facial Biometric Engine**: Hệ thống truy quét & đối khớp 1-vs-N sinh trắc học khuôn mặt trực quan qua **Cyber HUD Scanner** (`face_search.py`).
3. **Cyberpunk Feature Classifier GUI**: Giao diện đồ họa Futurist Neon cho phép trích xuất 5 điểm mốc Landmark, Vector SFace 128-D và phân loại giới tính (`face_feature_classifier.py`).
4. **Multi-Scale Dataset Categorizer**: Bộ tự động phân loại đa tỷ lệ ảnh thành các tập dữ liệu `nam_va_nu`, `only_nu`, `only_nam` (`classify_gender.py`).
5. **Image Scraper Suite**: Bộ cào ảnh tự động từ Google/Bing Images (`scrape_google_images.py`).
6. **Linux CLI Media Streamers**: Bộ phát phim & anime trực tiếp trên màn hình Linux Terminal bằng **Python / Pure Ruby & MPV** (`stream_cli.py` & `ani_cli.rb`).

---

## 📂 Cấu Trúc Kiến Trúc Dự Án (Project Architecture)

```text
c:\xampp\htdocs\shopkcvip.cc/
├── 🤖 BIOMETRIC & AI FACE ENGINES
│   ├── face_search.py              # Engine Truy quét 1-vs-N & Cyber HUD Scanner
│   ├── face_feature_classifier.py  # Module Trích xuất 128-D Vector & Cyberpunk GUI
│   ├── classify_gender.py          # Bộ phân loại Đa tỷ lệ (Multi-scale) Nam/Nữ
│   ├── face_detection_yunet_2023mar.onnx   # ONNX Model Phát hiện mặt YuNet (OpenCV Zoo)
│   └── face_recognition_sface_2021dec.onnx # ONNX Model Vector 128-D SFace (OpenCV Zoo)
│
├── 🕷️ MEDIA SCRAPERS & LINUX CLI STREAMERS
│   ├── scrape_google_images.py     # Tool cào ảnh tự động Google/Bing Images (Hỗ trợ udm=2)
│   ├── stream_cli.py               # Linux Terminal Streamer (Python + MPV + Tixel)
│   ├── ani_cli.rb                  # Linux Terminal Streamer (Pure Ruby + MPV)
│   └── ani-cli-pro.sh              # Shell script launcher cho Linux
│
├── 🖥️ LAUNCHERS & LOGS
│   ├── run_face_gui.bat            # Windows Double-Click Launcher cho Cyberpunk GUI
│   ├── gender_classification_report.json # Báo cáo phân loại dữ liệu dạng JSON
│   └── test_db/                    # Cơ sở dữ liệu ảnh nghiệm thu sinh trắc học
│       ├── nam_va_nu/              # Ảnh chứa cả Nam & Nữ
│       ├── only_nu/                # Ảnh chỉ có Nữ
│       └── only_nam/               # Ảnh chỉ có Nam
│
└── 🌐 WEB SYSTEM PLATFORM (PHP / MYSQL)
    ├── admin/                      # Trang quản trị AdminLTE
    ├── config/                     # Cấu hình Database & Webhooks
    ├── index.php                   # Trang chủ cổng dịch vụ
    └── .htaccess                   # Cấu hình Rewrite URL & Security
```

---

## 🚀 Hướng Dẫn Sử Dụng Chi Tiết (Usage Guide)

### 1. 👁️ Hệ thống Nhận diện & Đối khớp Sinh trắc học (`face_search.py`)

- **Khởi chạy Giao diện Cyber HUD Quét thời gian thực:**
  ```bash
  python face_search.py search --query "test_db/nguoi_dan_1.jpg" --db "test_db" --top-k 10 --gui
  ```
- **Phím tắt điều khiển trên cửa sổ HUD:**
  - `D` / `Mũi tên Phải (->)` : Chuyển sang **Trang Sau** (Trang 2, Trang 3...).
  - `A` / `Mũi tên Trái (<-)` : Quay lại **Trang Trước**.
  - `Q` / `ESC` : Thoát ứng dụng.

---

### 2. ⚡ Giao diện Cyberpunk Biometric Feature Classifier GUI (`face_feature_classifier.py`)

- **Khởi chạy nhanh qua file Batch (Double-Click):**
  Nhấp đôi chuột vào file [run_face_gui.bat](file:///c:/xampp/htdocs/shopkcvip.cc/run_face_gui.bat).
- **Hoặc khởi chạy từ dòng lệnh Terminal:**
  ```bash
  python face_feature_classifier.py --gui
  ```
- **Tính năng nổi bật:**
  - **Co dãn giao diện (Responsive Resizing)**: Canvas tự động co dãn khi phóng to/thu nhỏ cửa sổ.
  - **Bảng chỉ số Telemetry**: Hiển thị mốc 5-landmarks, tỷ lệ hàm, sắc tố da/môi và mẫu Vector 128-D.

---

### 3. 👩 Phân loại & Gán nhãn Hàng loạt Dataset (`classify_gender.py`)

- **Chạy phân loại tự động đa tỷ lệ cho thư mục dữ liệu:**
  ```bash
  python classify_gender.py "c:\xampp\htdocs\shopkcvip.cc\test_db\dataset_453"
  ```
- Kết quả sẽ tự động lưu vào các thư mục `nam_va_nu/`, `only_nu/`, `only_nam/` và xuất báo cáo `gender_classification_report.json`.

---

### 4. 🕷️ Cào & Tải Hình ảnh tự động (`scrape_google_images.py`)

- **Tải ảnh từ từ khóa / URL Google Search trực tiếp (Hỗ trợ `udm=2`):**
  ```bash
  python scrape_google_images.py --query "https://www.google.com/search?udm=2&q=site:xamvn.com+filetype:jpg" --out "xamvn_image" --max 50
  ```

---

### 5. 🍿 Xem Phim & Anime trực tiếp trên Linux Terminal CLI (`stream_cli.py` & `ani_cli.rb`)

- **Phiên bản Python Streamer:**
  ```bash
  python3 stream_cli.py "One Piece"
  ```
- **Xem video trực tiếp TRONG NỘI BỘ MÀN HÌNH TERMINAL (TTY Mode):**
  ```bash
  python3 stream_cli.py "Naruto" -e 1 -t
  ```
- **Phiên bản 100% Thuần Ruby (Pure Ruby Standard Library):**
  ```bash
  ruby ani_cli.rb "Attack on Titan" -e 5
  ```

---

## 🛡️ Yêu Cầu Môi Trường & Cài Đặt (Requirements)

- **Python**: 3.10+ (Đã thử nghiệm hoàn hảo trên Python 3.14).
- **Thư viện Python cần thiết**:
  ```bash
  pip install opencv-python numpy pillow requests beautifulsoup4
  ```
- **Môi trường Linux CLI (Cho Ani-CLI)**:
  ```bash
  sudo apt install mpv python3 ruby
  ```

---

## 📝 Giấy Phép & Bản Quyền

Dự án thuộc bản quyền phát triển chuyên sâu. Mọi mã nguồn đã được tối ưu hóa hiệu năng tối đa.
