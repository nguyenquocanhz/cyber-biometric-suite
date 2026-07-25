import os
# Tắt tất cả thông báo cảnh báo C++ không cần thiết của OpenCV DNN Backend
os.environ["OPENCV_LOG_LEVEL"] = "OFF"
os.environ["OPENCV_FFMPEG_CAPTURE_OPTIONS"] = "loglevel;quiet"

import cv2
try:
    cv2.utils.logging.setLogLevel(cv2.utils.logging.LOG_LEVEL_SILENT)
except Exception:
    pass

import numpy as np
import sys
import time
import argparse
import urllib.request
import json
import threading
from datetime import datetime
import tkinter as tk
from tkinter import filedialog, messagebox

# Tự động cấu hình mã hóa UTF-8 cho Terminal trên Windows để tránh lỗi Unicode
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

# Ngưỡng đối khớp chuẩn của mô hình SFace (OpenCV Zoo)
COSINE_THRESHOLD = 0.36
L2_THRESHOLD = 1.12

def download_models():
    """Tự động tải xuống các mô hình ONNX chính thức từ OpenCV Zoo nếu chưa có."""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    yunet_path = os.path.join(script_dir, "face_detection_yunet_2023mar.onnx")
    sface_path = os.path.join(script_dir, "face_recognition_sface_2021dec.onnx")
    
    yunet_url = "https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx"
    sface_url = "https://github.com/opencv/opencv_zoo/raw/main/models/face_recognition_sface/face_recognition_sface_2021dec.onnx"

    if not os.path.exists(yunet_path):
        print("📥 Đang tải mô hình phát hiện khuôn mặt YuNet từ OpenCV Zoo (khoảng 300 KB)...")
        try:
            urllib.request.urlretrieve(yunet_url, yunet_path)
            print("✓ Tải thành công YuNet!")
        except Exception as e:
            print(f"❌ Lỗi khi tải YuNet: {e}")
            sys.exit(1)
            
    if not os.path.exists(sface_path):
        print("📥 Đang tải mô hình trích xuất đặc trưng SFace từ OpenCV Zoo (khoảng 30 MB)...")
        try:
            urllib.request.urlretrieve(sface_url, sface_path)
            print("✓ Tải thành công SFace!")
        except Exception as e:
            print(f"❌ Lỗi khi tải SFace: {e}")
            sys.exit(1)
            
    return yunet_path, sface_path

class FaceEngine:
    def __init__(self, yunet_path, sface_path):
        self.detector = cv2.FaceDetectorYN.create(
            model=yunet_path,
            config="",
            input_size=(320, 320),
            score_threshold=0.8,
            nms_threshold=0.3,
            top_k=5000
        )
        self.recognizer = cv2.FaceRecognizerSF.create(
            model=sface_path,
            config=""
        )

    def extract_vector(self, img_or_path):
        """Trích xuất vector đặc trưng và landmark của khuôn mặt đầu tiên."""
        all_faces = self.extract_all_faces(img_or_path)
        if not all_faces:
            return None, None, None
        return all_faces[0]

    def extract_all_faces(self, img_or_path):
        """Trích xuất danh sách tất cả các khuôn mặt và 5 điểm mốc Landmark trong ảnh."""
        if isinstance(img_or_path, str):
            img = cv2.imread(img_or_path)
            if img is None:
                raise FileNotFoundError(f"Không thể đọc tệp ảnh tại đường dẫn: {img_or_path}")
        else:
            img = img_or_path
            
        h, w, _ = img.shape
        self.detector.setInputSize((w, h))
        
        _, faces = self.detector.detect(img)
        
        if faces is None or len(faces) == 0:
            return []
            
        results = []
        for face in faces:
            aligned_face = self.recognizer.alignCrop(img, face)
            feature_vector = self.recognizer.feature(aligned_face)
            cv2.normalize(feature_vector, feature_vector)
            
            landmarks = {
                "right_eye": (int(face[4]), int(face[5])),
                "left_eye": (int(face[6]), int(face[7])),
                "nose": (int(face[8]), int(face[9])),
                "right_mouth": (int(face[10]), int(face[11])),
                "left_mouth": (int(face[12]), int(face[13]))
            }
            results.append((feature_vector, face, landmarks))
            
        return results

    @staticmethod
    def compare_vectors(v1, v2):
        v1 = v1.flatten()
        v2 = v2.flatten()
        dot_product = np.dot(v1, v2)
        norm_v1 = np.linalg.norm(v1)
        norm_v2 = np.linalg.norm(v2)
        cosine_sim = dot_product / (norm_v1 * norm_v2) if (norm_v1 * norm_v2) > 0 else 0.0
        l2_dist = np.linalg.norm(v1 - v2)
        return float(cosine_sim), float(l2_dist)

    @staticmethod
    def batch_compare(q_vector, db_vectors_dict):
        """Tính toán ma trận Cosine & L2 Distance cực nhanh bằng NumPy."""
        paths = list(db_vectors_dict.keys())
        if not paths:
            return []
        
        matrix = np.array([db_vectors_dict[p] for p in paths], dtype=np.float32)
        q_norm = q_vector.flatten()
        
        norms = np.linalg.norm(matrix, axis=1, keepdims=True)
        norms[norms == 0] = 1e-6
        matrix_normalized = matrix / norms
        
        cosine_sims = np.dot(matrix_normalized, q_norm)
        l2_dists = np.sqrt(np.maximum(0.0, 2.0 - 2.0 * cosine_sims))
        
        results = []
        for i in range(len(paths)):
            results.append((paths[i], float(cosine_sims[i]), float(l2_dists[i])))
            
        results.sort(key=lambda x: x[1], reverse=True)
        return results

class DatabaseWatcher:
    """Bộ tự động giám sát thư mục CSDL real-time: Tự động nạp đặc trưng ngay khi có ảnh mới được thêm vào."""
    def __init__(self, db_folder, engine, cache_file=None, interval=2.0, on_update_callback=None):
        self.db_folder = os.path.abspath(db_folder)
        self.engine = engine
        self.cache_file = cache_file or os.path.join(self.db_folder, "face_signatures_cache.json")
        self.interval = interval
        self.on_update_callback = on_update_callback
        self.running = False
        self.thread = None
        self.known_files = set()

    def start(self):
        if self.running:
            return
        self.running = True
        self.thread = threading.Thread(target=self._watch_loop, daemon=True)
        self.thread.start()
        print(f"👁️ [AUTO-SYNC MONITOR] Đã kích hoạt tự động giám sát thư mục CSDL: {self.db_folder}")

    def stop(self):
        self.running = False

    def _get_current_files(self):
        valid_exts = (".jpg", ".jpeg", ".png", ".webp")
        current_files = set()
        for root, _, files in os.walk(self.db_folder):
            for file in files:
                if file.lower().endswith(valid_exts):
                    current_files.add(os.path.abspath(os.path.join(root, file)))
        return current_files

    def _watch_loop(self):
        self.known_files = self._get_current_files()
        
        while self.running:
            try:
                time.sleep(self.interval)
                current_files = self._get_current_files()
                
                added_files = current_files - self.known_files
                removed_files = self.known_files - current_files

                if added_files or removed_files:
                    if added_files:
                        print(f"\n🔔 [AUTO-SYNC] Phát hiện {len(added_files)} tệp ảnh mới trong thư mục!")
                        for f in added_files:
                            print(f"   └─ 🆕 Tự động nạp đặc trưng ảnh mới: {os.path.basename(f)}")
                    if removed_files:
                        print(f"\n🔔 [AUTO-SYNC] Phát hiện {len(removed_files)} tệp ảnh đã xóa khỏi thư mục!")

                    updated_db_vectors = update_cache_smart(self.db_folder, self.engine, self.cache_file)
                    self.known_files = current_files

                    if self.on_update_callback:
                        self.on_update_callback(updated_db_vectors)

            except Exception as e:
                print(f"⚠️ [AUTO-SYNC ERROR] Lỗi khi theo dõi thư mục: {e}")

# --- HUD DRAWING FUNCTIONS WITH DYNAMIC RESPONSIVE SCALING ---

def draw_cyber_grid(hud):
    """Vẽ mạng lưới grid mờ phủ toàn bộ kích thước màn hình linh hoạt."""
    h, w, _ = hud.shape
    grid_size = max(30, min(w, h) // 20)
    for x in range(0, w, grid_size):
        cv2.line(hud, (x, 0), (x, h), (15, 25, 35), 1)
    for y in range(0, h, grid_size):
        cv2.line(hud, (0, y), (w, y), (15, 25, 35), 1)

def draw_landmarks_on_crop(img_crop, landmarks, bbox):
    """Vẽ 5 điểm mốc Landmark sinh trắc học tỏa sáng Cyber."""
    if not landmarks:
        return img_crop
    
    x_f, y_f, w_f, h_f = int(bbox[0]), int(bbox[1]), int(bbox[2]), int(bbox[3])
    if w_f <= 0 or h_f <= 0:
        return img_crop

    c_h, c_w, _ = img_crop.shape
    scale_x = c_w / float(w_f)
    scale_y = c_h / float(h_f)

    mapped_pts = {}
    for k, (px, py) in landmarks.items():
        mx = int((px - x_f) * scale_x)
        my = int((py - y_f) * scale_y)
        mapped_pts[k] = (mx, my)

    pts = np.array([
        mapped_pts["right_eye"], mapped_pts["left_eye"],
        mapped_pts["left_mouth"], mapped_pts["right_mouth"]
    ], np.int32).reshape((-1, 1, 2))
    cv2.polylines(img_crop, [pts], True, (0, 150, 255), 1)

    for eye in [mapped_pts["right_eye"], mapped_pts["left_eye"]]:
        cv2.circle(img_crop, eye, 4, (0, 255, 255), -1)
        cv2.circle(img_crop, eye, 7, (0, 255, 255), 1)

    cv2.circle(img_crop, mapped_pts["nose"], 4, (0, 255, 0), -1)

    for m in [mapped_pts["right_mouth"], mapped_pts["left_mouth"]]:
        cv2.circle(img_crop, m, 4, (255, 0, 255), -1)

    return img_crop

def draw_cyber_box(img, x1, y1, x2, y2, color=(0, 255, 255), label="", thickness=1):
    """Vẽ hộp chữ nhật phong cách viễn tưởng với các góc bo dày."""
    cv2.rectangle(img, (x1, y1), (x2, y2), (color[0]//4, color[1]//4, color[2]//4), thickness)
    g_len = max(8, min(x2 - x1, y2 - y1) // 10)
    
    cv2.line(img, (x1, y1), (x1 + g_len, y1), color, thickness + 2)
    cv2.line(img, (x1, y1), (x1, y1 + g_len), color, thickness + 2)
    cv2.line(img, (x2, y1), (x2 - g_len, y1), color, thickness + 2)
    cv2.line(img, (x2, y1), (x2, y1 + g_len), color, thickness + 2)
    cv2.line(img, (x1, y2), (x1 + g_len, y2), color, thickness + 2)
    cv2.line(img, (x1, y2), (x1, y2 - g_len), color, thickness + 2)
    cv2.line(img, (x2, y2), (x2 - g_len, y2), color, thickness + 2)
    cv2.line(img, (x2, y2), (x2, y2 - g_len), color, thickness + 2)
    
    if label:
        font_scale = max(0.32, min(0.85, (x2 - x1) / 500.0))
        text_size = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, font_scale, 1)[0]
        lbl_h = text_size[1] + 8
        cv2.rectangle(img, (x1, y1 - lbl_h), (x1 + text_size[0] + 10, y1), (color[0]//3, color[1]//3, color[2]//3), -1)
        cv2.rectangle(img, (x1, y1 - lbl_h), (x1 + text_size[0] + 10, y1), color, 1)
        cv2.putText(img, label, (x1 + 5, y1 - 4), cv2.FONT_HERSHEY_SIMPLEX, font_scale, (255, 255, 255), 1)

def draw_cyber_header(hud, status_text="ACTIVE", status_color=(0, 255, 0)):
    """Vẽ thanh tiêu đề co dãn theo chiều rộng cửa sổ."""
    h, w, _ = hud.shape
    hdr_h = 55
    cv2.rectangle(hud, (0, 0), (w, hdr_h), (10, 15, 20), -1)
    cv2.line(hud, (0, hdr_h), (w, hdr_h), (0, 255, 255), 2)
    
    font_scale = max(0.45, min(0.75, w / 1400.0))
    cv2.putText(hud, "NATIONAL CYBER FORENSICS - FACIAL IDENTIFICATION SYSTEM", (20, 36),
                cv2.FONT_HERSHEY_SIMPLEX, font_scale, (255, 255, 255), 2)
    
    status_w = max(180, int(w * 0.22))
    status_x = w - status_w - 20
    cv2.rectangle(hud, (status_x, 12), (w - 20, 42), (20, 25, 30), -1)
    cv2.rectangle(hud, (status_x, 12), (w - 20, 42), (80, 80, 80), 1)
    cv2.putText(hud, "STATUS:", (status_x + 10, 33), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (180, 180, 180), 1)
    cv2.putText(hud, status_text, (status_x + 80, 33), cv2.FONT_HERSHEY_SIMPLEX, 0.45, status_color, 2)

def draw_progress_bar(img, x, y, width, height, percent, color=(0, 255, 0)):
    """Vẽ thanh phần trăm loading mượt mà."""
    cv2.rectangle(img, (x, y), (x + width, y + height), (40, 40, 40), -1)
    cv2.rectangle(img, (x, y), (x + width, y + height), (80, 80, 80), 1)
    fill_w = int(width * (percent / 100.0))
    if fill_w > 0:
        cv2.rectangle(img, (x, y), (x + fill_w, y + height), color, -1)
        step = max(5, width // 30)
        for i in range(x + step, x + fill_w, step):
            cv2.line(img, (i, y), (i, y + height), (0, 0, 0), 1)

def update_cache_smart(db_folder, engine, cache_file):
    """Đồng bộ kho dữ liệu ảnh và lưu cache vector đặc trưng + landmark."""
    cache_data = {}
    if os.path.exists(cache_file):
        try:
            with open(cache_file, "r", encoding="utf-8") as f:
                cache_data = json.load(f)
        except Exception:
            cache_data = {}

    valid_exts = (".jpg", ".jpeg", ".png", ".webp")
    current_files = []
    for root, _, files in os.walk(db_folder):
        for file in files:
            if file.lower().endswith(valid_exts):
                current_files.append(os.path.abspath(os.path.join(root, file)))

    normalized_cache = {}
    for k, v in cache_data.items():
        normalized_cache[os.path.abspath(k)] = v
    cache_data = normalized_cache

    cache_updated = False
    
    # 1. Xóa ảnh không còn tồn tại
    keys_to_remove = [k for k in cache_data.keys() if k not in current_files]
    if keys_to_remove:
        print(f"🗑️ Phát hiện {len(keys_to_remove)} tệp ảnh đã xóa khỏi CSDL. Đang dọn dẹp cache...")
        for k in keys_to_remove:
            del cache_data[k]
        cache_updated = True

    # 2. Tự động phát hiện và nạp ảnh mới vừa thêm vào thư mục
    new_files = [f for f in current_files if f not in cache_data]
    if new_files:
        print(f"🔄 Phát hiện {len(new_files)} tệp ảnh mới trong thư mục. Đang tự động nạp đặc trưng...")
        for idx, f in enumerate(new_files):
            try:
                vector, face, landmarks = engine.extract_vector(f)
                if vector is not None:
                    cache_data[f] = {
                        "vector": vector.flatten().tolist(),
                        "landmarks": landmarks
                    }
                    cache_updated = True
                print(f"   ├─ [{idx+1}/{len(new_files)}] Nạp đặc trưng thành công: {os.path.basename(f)}")
            except Exception as e:
                print(f"   ├─ [{idx+1}/{len(new_files)}] ⚠️ Lỗi khi xử lý {os.path.basename(f)}: {e}")

    if cache_updated:
        with open(cache_file, "w", encoding="utf-8") as f:
            json.dump(cache_data, f, ensure_ascii=False, indent=2)
        print("✓ Đồng bộ và cập nhật file cache thành công!")
        
    db_vectors = {}
    for k, v in cache_data.items():
        if isinstance(v, dict) and "vector" in v:
            db_vectors[k] = v["vector"]
        else:
            db_vectors[k] = v
            
    return db_vectors

# --- RESPONSIVE CYBER HUD SCANNER WITH MULTI-PAGE & MULTI-COLUMN CANDIDATES ---

def run_gui_search(engine, query_path, db_vectors, top_k=10, export_file=None):
    """Giao diện Cyber HUD quét và tìm kiếm khuôn mặt hỗ trợ HIỂN THỊ ĐA DẠNG NGHI PHẠM VÀ PHÂN TRANG (PAGINATION)."""
    q_img = cv2.imread(query_path)
    if q_img is None:
        print(f"❌ Không thể đọc ảnh truy vấn tại: {query_path}")
        return []
    
    all_q_faces = engine.extract_all_faces(q_img)
    if not all_q_faces:
        print("❌ Lỗi: Không phát hiện khuôn mặt trong ảnh truy vấn.")
        return []
        
    q_vector, q_face, q_landmarks = all_q_faces[0]
    q_h, q_w, _ = q_img.shape

    if q_face is not None:
        x_f, y_f, w_f, h_f = int(q_face[0]), int(q_face[1]), int(q_face[2]), int(q_face[3])
        pad_x, pad_y = int(w_f * 0.3), int(h_f * 0.3)
        cx1 = max(0, x_f - pad_x)
        cy1 = max(0, y_f - pad_y)
        cx2 = min(q_w, x_f + w_f + pad_x)
        cy2 = min(q_h, y_f + h_f + pad_y)
        crop_q = q_img[cy1:cy2, cx1:cx2].copy()
    else:
        crop_q = q_img.copy()

    cv2.namedWindow("CYBER FACIAL SCANNER HUD", cv2.WINDOW_NORMAL)
    cv2.resizeWindow("CYBER FACIAL SCANNER HUD", 1360, 768)

    laser_y = 0
    laser_dir = 8
    
    results = FaceEngine.batch_compare(q_vector, db_vectors)
    num_records = len(results)
    
    start_time = time.time()

    # VÒNG LẶP QUÉT TÌM KIẾM (SCANNING PHASE)
    for idx, (img_path, cosine_sim, l2_dist) in enumerate(results):
        rect = cv2.getWindowImageRect("CYBER FACIAL SCANNER HUD")
        hud_w = rect[2] if rect[2] > 600 else 1360
        hud_h = rect[3] if rect[3] > 400 else 768

        hud = np.zeros((hud_h, hud_w, 3), dtype=np.uint8)
        draw_cyber_grid(hud)
        draw_cyber_header(hud, status_text=f"SCANNING {idx+1}/{num_records}", status_color=(0, 180, 255))

        box_w = int(hud_w * 0.30)
        box_h = int(hud_h * 0.50)
        box_y1 = 80
        box_y2 = box_y1 + box_h

        # 1. Khung bên trái: Suspect Query
        sq_x1 = int(hud_w * 0.03)
        sq_x2 = sq_x1 + box_w
        crop_q_resized = cv2.resize(crop_q, (box_w, box_h))
        crop_q_landmarks = draw_landmarks_on_crop(crop_q_resized.copy(), q_landmarks, q_face)
        hud[box_y1:box_y2, sq_x1:sq_x2] = crop_q_landmarks
        draw_cyber_box(hud, sq_x1, box_y1, sq_x2, box_y2, color=(0, 255, 255), label="SUSPECT QUERY (5-LANDMARKS)")

        cx_p, cy_p = (sq_x1 + sq_x2) // 2, (box_y1 + box_y2) // 2
        pulse = int(127 + 127 * np.sin(time.time() * 8))
        cv2.circle(hud, (cx_p, cy_p), max(15, box_w // 10), (0, 0, pulse), 2)
        cv2.line(hud, (cx_p, cy_p - 30), (cx_p, cy_p + 30), (0, 0, pulse), 1)
        cv2.line(hud, (cx_p - 30, cy_p), (cx_p + 30, cy_p), (0, 0, pulse), 1)

        # 2. Khung ở giữa: Target File
        t_img = cv2.imread(img_path)
        if t_img is not None:
            t_resized = cv2.resize(t_img, (box_w, box_h))
            laser_y = (laser_y + laser_dir) % box_h
            t_scan = t_resized.copy()
            cv2.line(t_scan, (0, laser_y), (box_w, laser_y), (0, 255, 0), 2)
            overlay = t_scan.copy()
            cv2.rectangle(overlay, (0, max(0, laser_y - 8)), (box_w, min(box_h, laser_y + 8)), (0, 255, 0), -1)
            cv2.addWeighted(overlay, 0.25, t_scan, 0.75, 0, t_scan)

            sc_x1 = int(hud_w * 0.36)
            sc_x2 = sc_x1 + box_w
            hud[box_y1:box_y2, sc_x1:sc_x2] = t_scan
            draw_cyber_box(hud, sc_x1, box_y1, sc_x2, box_y2, color=(0, 255, 0), label="SCANNING TARGET FILE")

        # 3. Khung bên phải: Stats
        info_x = int(hud_w * 0.69)
        info_w = hud_w - info_x - 30
        
        cv2.putText(hud, "DATABASE TARGET:", (info_x, 100), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (150, 150, 150), 1)
        cv2.putText(hud, os.path.basename(img_path)[:25], (info_x, 125), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (0, 255, 255), 1)
        cv2.putText(hud, f"COSINE SIM: {cosine_sim:.4f}", (info_x, 155), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (150, 150, 150), 1)
        cv2.putText(hud, f"L2 DISTANCE: {l2_dist:.4f}", (info_x, 180), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (150, 150, 150), 1)

        prog_percent = int(((idx + 1) / num_records) * 100)
        cv2.putText(hud, f"PROGRESS: {prog_percent}%", (info_x, 215), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (150, 150, 150), 1)
        draw_progress_bar(hud, info_x, 230, max(100, info_w), 12, prog_percent, color=(0, 180, 255))

        cv2.putText(hud, "TOP MATCHES FOUND:", (info_x, 275), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (200, 200, 200), 1)
        for i, (match_path, match_score, _) in enumerate(results[:5]):
            y_pos = 305 + i * 30
            match_name = os.path.basename(match_path)[:18]
            label_col = (0, 255, 0) if match_score >= COSINE_THRESHOLD else (100, 100, 100)
            cv2.putText(hud, f"#{i+1} {match_name:<18} Score: {match_score:.3f}", (info_x, y_pos),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, label_col, 1)

        footer_y = hud_h - 20
        cv2.putText(hud, ">> RESPONSIVE HUD ENGINE - AUTO-RESIZING ENABLED - PRESS 'q' TO SKIP SCAN", (30, footer_y),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.4, (80, 100, 120), 1)

        cv2.imshow("CYBER FACIAL SCANNER HUD", hud)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    total_duration = time.time() - start_time
    
    # Nếu top_k = 0 hoặc lớn hơn số lượng bản ghi thì lấy toàn bộ
    if top_k <= 0 or top_k > num_records:
        top_k = num_records

    top_matches = results[:top_k]
    
    current_page = 0
    items_per_page = 4  # Hiển thị 4 thẻ đối tượng trên mỗi trang (2 cột x 2 hàng)

    # --- KẾT QUẢ KẾT THÚC CÓ PHÂN TRANG (FINAL VIEW WITH PAGINATION) ---
    while True:
        rect = cv2.getWindowImageRect("CYBER FACIAL SCANNER HUD")
        hud_w = rect[2] if rect[2] > 600 else 1360
        hud_h = rect[3] if rect[3] > 400 else 768

        hud = np.zeros((hud_h, hud_w, 3), dtype=np.uint8)
        draw_cyber_grid(hud)
        draw_cyber_header(hud, status_text="COMPLETED", status_color=(0, 255, 0))

        # Trái: Suspect Query & System Logs
        box_w = int(hud_w * 0.26)
        box_h = int(hud_h * 0.45)
        box_y1 = 80
        box_y2 = box_y1 + box_h

        sq_x1 = int(hud_w * 0.03)
        sq_x2 = sq_x1 + box_w
        crop_q_resized = cv2.resize(crop_q, (box_w, box_h))
        crop_q_landmarks = draw_landmarks_on_crop(crop_q_resized.copy(), q_landmarks, q_face)
        hud[box_y1:box_y2, sq_x1:sq_x2] = crop_q_landmarks
        draw_cyber_box(hud, sq_x1, box_y1, sq_x2, box_y2, color=(0, 255, 255), label="SUSPECT QUERY (5-LANDMARKS)")

        stats_y = box_y2 + 30
        cv2.putText(hud, "SYSTEM IDENTIFICATION LOGS:", (sq_x1, stats_y), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (180, 180, 180), 2)
        cv2.putText(hud, f"- Suspect: {os.path.basename(query_path)[:22]}", (sq_x1, stats_y + 20), cv2.FONT_HERSHEY_SIMPLEX, 0.4, (130, 130, 130), 1)
        cv2.putText(hud, f"- Total DB Scanned: {num_records} files", (sq_x1, stats_y + 40), cv2.FONT_HERSHEY_SIMPLEX, 0.4, (130, 130, 130), 1)
        cv2.putText(hud, f"- Top Matches Listed: {len(top_matches)} candidates", (sq_x1, stats_y + 60), cv2.FONT_HERSHEY_SIMPLEX, 0.4, (0, 255, 255), 1)
        cv2.putText(hud, f"- Scan Time: {total_duration:.4f}s", (sq_x1, stats_y + 80), cv2.FONT_HERSHEY_SIMPLEX, 0.4, (130, 130, 130), 1)
        
        best_score = top_matches[0][1] if top_matches else 0.0
        status_msg = "[MATCH] VERIFIED MATCH FOUND" if best_score >= COSINE_THRESHOLD else "[NO MATCH] MISMATCH"
        cv2.putText(hud, f"- Verdict: {status_msg}", (sq_x1, stats_y + 105), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (0, 255, 255), 1)

        # Phân trang Thẻ kết quả nghi phạm bên phải
        total_pages = max(1, (len(top_matches) + items_per_page - 1) // items_per_page)
        if current_page >= total_pages:
            current_page = total_pages - 1
            
        start_idx = current_page * items_per_page
        page_items = top_matches[start_idx:start_idx + items_per_page]

        right_start_x = int(hud_w * 0.32)
        right_w = hud_w - right_start_x - 20
        
        # Tiêu đề thanh phân trang
        page_info_str = f"IDENTIFIED CANDIDATES (PAGE {current_page+1}/{total_pages} - DISPLAYING #{start_idx+1}-#{start_idx+len(page_items)} OF {len(top_matches)})"
        cv2.putText(hud, page_info_str, (right_start_x, 72), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (0, 255, 255), 1)
        cv2.putText(hud, "[Press 'A' / 'D' or LEFT / RIGHT ARROW to Switch Pages]", (right_start_x + right_w - 420, 72), cv2.FONT_HERSHEY_SIMPLEX, 0.4, (150, 150, 150), 1)

        # Bố cục 2 Cột x 2 Hàng (Bốn thẻ nghi phạm mỗi trang)
        col_w = (right_w - 15) // 2
        card_h = (hud_h - 140) // 2

        for idx_on_page, (img_path, cosine_sim, l2_dist) in enumerate(page_items):
            global_rank = start_idx + idx_on_page + 1
            row = idx_on_page // 2
            col = idx_on_page % 2
            
            c_x = right_start_x + col * (col_w + 15)
            c_y = 85 + row * (card_h + 15)
            
            target_img = cv2.imread(img_path)
            crop_t_resized = None
            if target_img is not None:
                t_faces = engine.extract_all_faces(target_img)
                if t_faces:
                    _, t_face, t_landmarks = t_faces[0]
                    tx_f, ty_f, tw_f, th_f = int(t_face[0]), int(t_face[1]), int(t_face[2]), int(t_face[3])
                    t_pad_x, t_pad_y = int(tw_f * 0.3), int(th_f * 0.3)
                    tcx1 = max(0, tx_f - t_pad_x)
                    tcy1 = max(0, ty_f - t_pad_y)
                    tcx2 = min(target_img.shape[1], tx_f + tw_f + t_pad_x)
                    tcy2 = min(target_img.shape[0], ty_f + th_f + t_pad_y)
                    crop_t = target_img[tcy1:tcy2, tcx1:tcx2].copy()
                    crop_t_resized = cv2.resize(crop_t, (card_h - 30, card_h - 30))
                    crop_t_resized = draw_landmarks_on_crop(crop_t_resized, t_landmarks, t_face)
                else:
                    crop_t_resized = cv2.resize(target_img, (card_h - 30, card_h - 30))

            border_col = (0, 255, 0) if cosine_sim >= COSINE_THRESHOLD else (0, 0, 255)
            draw_cyber_box(hud, c_x, c_y, c_x + col_w, c_y + card_h,
                           color=border_col, label=f"RANK #{global_rank} MATCH")

            if crop_t_resized is not None:
                hud[c_y + 15:c_y + 15 + (card_h - 30), c_x + 10:c_x + 10 + (card_h - 30)] = crop_t_resized

            name_x = c_x + card_h - 10
            cv2.putText(hud, f"FILE: {os.path.basename(img_path)[:20]}", (name_x, c_y + 28),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, (255, 255, 255), 1)
            cv2.putText(hud, f"Cosine: {cosine_sim:.4f} | L2: {l2_dist:.4f}", (name_x, c_y + 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.38, (180, 180, 180), 1)

            if cosine_sim >= COSINE_THRESHOLD:
                confidence = 70.0 + (cosine_sim - COSINE_THRESHOLD) / (1.0 - COSINE_THRESHOLD) * 30.0
            else:
                confidence = max(0.0, (cosine_sim + 1.0) / (COSINE_THRESHOLD + 1.0) * 70.0)

            pbar_w = max(60, col_w - (card_h + 80))
            draw_progress_bar(hud, name_x, c_y + 70, pbar_w, 8, confidence, color=border_col)
            cv2.putText(hud, f"{confidence:.1f}%", (name_x + pbar_w + 10, c_y + 78), cv2.FONT_HERSHEY_SIMPLEX, 0.4, border_col, 2)

            status_label = "[MATCH] VERIFIED MATCH" if cosine_sim >= COSINE_THRESHOLD else "[MISMATCH] MISMATCH"
            cv2.putText(hud, status_label, (name_x, c_y + 105), cv2.FONT_HERSHEY_SIMPLEX, 0.4, border_col, 2)

        footer_y = hud_h - 18
        nav_hint = f">> PAGE {current_page+1}/{total_pages} | PRESS 'A' / 'D' TO SWITCH PAGES | PRESS 'Q' TO QUIT"
        cv2.putText(hud, nav_hint, (30, footer_y), cv2.FONT_HERSHEY_SIMPLEX, 0.42, (0, 255, 255), 1)

        cv2.imshow("CYBER FACIAL SCANNER HUD", hud)
        
        # Bắt phím điều hướng chuyển trang
        key = cv2.waitKey(50) & 0xFF
        if key == ord('q'):
            break
        elif key in (ord('a'), ord('A'), 81, 2424832):  # Phím A hoặc Mũi tên trái
            current_page = max(0, current_page - 1)
        elif key in (ord('d'), ord('D'), 83, 2555904):  # Phím D hoặc Mũi tên phải
            current_page = min(total_pages - 1, current_page + 1)

    cv2.destroyAllWindows()

    if export_file:
        export_report(query_path, db_vectors, results, top_k, total_duration, export_file)

    return results

def export_report(query_path, db_vectors, results, top_k, duration, export_file):
    """Xuất báo cáo giám định sinh trắc học định dạng JSON."""
    report_data = {
        "title": "NATIONAL CYBER FORENSICS - BIOMETRIC IDENTIFICATION REPORT",
        "timestamp": datetime.now().isoformat(),
        "query_image": os.path.abspath(query_path),
        "total_records_scanned": len(db_vectors),
        "scan_duration_seconds": round(duration, 4),
        "top_matches": []
    }
    
    for idx, (img_path, cosine_sim, l2_dist) in enumerate(results[:top_k]):
        matched = cosine_sim >= COSINE_THRESHOLD
        conf = 70.0 + (cosine_sim - COSINE_THRESHOLD) / (1.0 - COSINE_THRESHOLD) * 30.0 if matched else max(0.0, (cosine_sim + 1.0) / (COSINE_THRESHOLD + 1.0) * 70.0)
        report_data["top_matches"].append({
            "rank": idx + 1,
            "filepath": os.path.abspath(img_path),
            "filename": os.path.basename(img_path),
            "cosine_similarity": round(cosine_sim, 4),
            "l2_distance": round(l2_dist, 4),
            "confidence_percentage": round(conf, 2),
            "verdict": "VERIFIED_MATCH" if matched else "MISMATCH"
        })
        
    with open(export_file, "w", encoding="utf-8") as f:
        json.dump(report_data, f, ensure_ascii=False, indent=2)
    print(f"\n📄 Đã xuất báo cáo giám định thành công tại: {os.path.abspath(export_file)}")

# --- TKINTER LAUNCHER GUI WITH RESPONSIVE FLEXIBLE RESIZING & TOP-K SELECTOR ---

def run_launcher_gui(engine):
    """Mở giao diện Launcher đồ họa có tùy chọn TOP-K HIỂN THỊ ĐA DẠNG KẾT QUẢ."""
    root = tk.Tk()
    root.title("CYBER SECURITY - BIOMETRIC SYSTEM LAUNCHER")
    root.geometry("680x520")
    root.minsize(640, 480)
    root.resizable(True, True)
    root.configure(bg="#0B0F14")
    
    root.columnconfigure(0, weight=1)
    root.rowconfigure(0, weight=0)
    root.rowconfigure(1, weight=1)
    root.rowconfigure(2, weight=0)

    header_frame = tk.Frame(root, bg="#0B0F14")
    header_frame.grid(row=0, column=0, sticky="ew", pady=15)
    
    title_label = tk.Label(header_frame, text="NATIONAL CYBER FORENSICS", bg="#0B0F14", fg="#00FFFF", font=("Courier", 16, "bold"))
    title_label.pack()
    
    subtitle_label = tk.Label(header_frame, text="Biometric Facial Identification System v3.0 (Multi-Candidate & Pagination)", bg="#0B0F14", fg="#80A0C0", font=("Courier", 10))
    subtitle_label.pack(pady=3)

    query_path_var = tk.StringVar()
    db_path_var = tk.StringVar()
    top_k_var = tk.IntVar(value=10)
    watcher_ref = [None]

    frame = tk.Frame(root, bg="#0B0F14")
    frame.grid(row=1, column=0, sticky="nsew", padx=30, pady=10)
    frame.columnconfigure(0, weight=1)

    # 1. Suspect Query Photo
    tk.Label(frame, text="SUSPECT QUERY PHOTO (ẢNH ĐỐI TƯỢNG):", bg="#0B0F14", fg="#FFFFFF", font=("Courier", 9, "bold")).grid(row=0, column=0, sticky="w", pady=(5, 2))
    
    q_input_frame = tk.Frame(frame, bg="#0B0F14")
    q_input_frame.grid(row=1, column=0, sticky="ew", pady=5)
    q_input_frame.columnconfigure(0, weight=1)

    query_entry = tk.Entry(q_input_frame, textvariable=query_path_var, bg="#151A20", fg="#00FFFF", insertbackground="white", relief="flat", font=("Courier", 10))
    query_entry.grid(row=0, column=0, sticky="ew", ipady=4)

    def browse_query():
        file_path = filedialog.askopenfilename(
            title="Chọn ảnh nghi phạm cần đối khớp",
            filetypes=[("Image Files", "*.jpg *.jpeg *.png *.webp")]
        )
        if file_path:
            query_path_var.set(os.path.abspath(file_path))

    btn_browse_q = tk.Button(q_input_frame, text="CHỌN ẢNH...", command=browse_query, bg="#0088FF", fg="#FFFFFF", relief="flat", font=("Courier", 9, "bold"), activebackground="#0055CC", padx=10)
    btn_browse_q.grid(row=0, column=1, padx=(8, 0))

    # 2. Civilian Database Folder
    tk.Label(frame, text="CIVILIAN PHOTO DATABASE (KHO ẢNH NHÂN DẠNG CSDL):", bg="#0B0F14", fg="#FFFFFF", font=("Courier", 9, "bold")).grid(row=2, column=0, sticky="w", pady=(12, 2))
    
    db_input_frame = tk.Frame(frame, bg="#0B0F14")
    db_input_frame.grid(row=3, column=0, sticky="ew", pady=5)
    db_input_frame.columnconfigure(0, weight=1)

    db_entry = tk.Entry(db_input_frame, textvariable=db_path_var, bg="#151A20", fg="#00FFFF", insertbackground="white", relief="flat", font=("Courier", 10))
    db_entry.grid(row=0, column=0, sticky="ew", ipady=4)

    def browse_db():
        dir_path = filedialog.askdirectory(title="Chọn thư mục chứa ảnh dân cư CSDL")
        if dir_path:
            abs_dir = os.path.abspath(dir_path)
            db_path_var.set(abs_dir)
            
            if watcher_ref[0]:
                watcher_ref[0].stop()
                
            watcher_ref[0] = DatabaseWatcher(abs_dir, engine)
            watcher_ref[0].start()
            status_lbl.config(text="STATUS: 🟢 ACTIVE - Tự động giám sát & nạp đặc trưng ảnh mới real-time", fg="#00FF00")

    btn_browse_db = tk.Button(db_input_frame, text="CHỌN THƯ MỤC...", command=browse_db, bg="#0088FF", fg="#FFFFFF", relief="flat", font=("Courier", 9, "bold"), activebackground="#0055CC", padx=10)
    btn_browse_db.grid(row=0, column=1, padx=(8, 0))

    status_lbl = tk.Label(frame, text="STATUS: Standby - Select database folder to enable auto-sync", bg="#0B0F14", fg="#80A0C0", font=("Courier", 8, "italic"))
    status_lbl.grid(row=4, column=0, sticky="w", pady=3)

    # 3. Option Top-K Candidates Selection
    topk_frame = tk.Frame(frame, bg="#0B0F14")
    topk_frame.grid(row=5, column=0, sticky="w", pady=(10, 5))
    
    tk.Label(topk_frame, text="SỐ LƯỢNG NGHI PHẠM HIỂN THỊ (TOP-K MATCHES):", bg="#0B0F14", fg="#00FFFF", font=("Courier", 9, "bold")).pack(side="left")
    topk_spin = tk.Spinbox(topk_frame, from_=1, to=200, textvariable=top_k_var, width=6, bg="#151A20", fg="#00FF00", insertbackground="white", relief="flat", font=("Courier", 10, "bold"))
    topk_spin.pack(side="left", padx=10)
    tk.Label(topk_frame, text="(Nhập số lớn để liệt kê nhiều hình ảnh)", bg="#0B0F14", fg="#80A0C0", font=("Courier", 8, "italic")).pack(side="left")

    # Start Button Frame
    btn_frame = tk.Frame(root, bg="#0B0F14")
    btn_frame.grid(row=2, column=0, sticky="ew", pady=15)

    def start_scan():
        q_p = query_path_var.get().strip()
        db_p = db_path_var.get().strip()
        
        if not q_p or not os.path.exists(q_p):
            messagebox.showerror("Lỗi dữ liệu", "Vui lòng chọn tệp ảnh đối tượng hợp lệ!")
            return
        if not db_p or not os.path.isdir(db_p):
            messagebox.showerror("Lỗi dữ liệu", "Vui lòng chọn thư mục chứa kho ảnh nhân dạng dân cư!")
            return
            
        top_k_val = top_k_var.get()

        if watcher_ref[0]:
            watcher_ref[0].stop()

        root.destroy()
        cache_file = os.path.join(db_p, "face_signatures_cache.json")
        db_vectors = update_cache_smart(db_p, engine, cache_file)
        run_gui_search(engine, q_p, db_vectors, top_k=top_k_val)

    btn_start = tk.Button(btn_frame, text="⚡ START FORENSIC SCAN (CYBER HUD) ⚡", command=start_scan, bg="#00FF00", fg="#000000", relief="flat", font=("Courier", 11, "bold"), pady=8, padx=20, activebackground="#00CC00")
    btn_start.pack()

    root.update_idletasks()
    w_w, w_h = root.winfo_width(), root.winfo_height()
    screen_w, screen_h = root.winfo_screenwidth(), root.winfo_screenheight()
    x_c = (screen_w - w_w) // 2
    y_c = (screen_h - w_h) // 2
    root.geometry(f"{w_w}x{w_h}+{x_c}+{y_c}")

    root.mainloop()

# --- MAIN CLI & ENTRYPOINT ---

def main():
    parser = argparse.ArgumentParser(
        description="Hệ thống so sánh vector khuôn mặt và truy quét đối tượng trong Cơ sở dữ liệu Dân cư"
    )
    subparsers = parser.add_subparsers(dest="command", help="Các lệnh chức năng")

    # Subcommand: Compare 1-vs-1
    parser_compare = subparsers.add_parser("compare", help="So sánh đối khớp danh tính giữa 2 tấm ảnh chân dung")
    parser_compare.add_argument("--img1", required=True, help="Đường dẫn ảnh chân dung thứ nhất")
    parser_compare.add_argument("--img2", required=True, help="Đường dẫn ảnh chân dung thứ hai")

    # Subcommand: Search 1-vs-N
    parser_search = subparsers.add_parser("search", help="Truy quét tìm kiếm đối tượng trong thư mục CSDL")
    parser_search.add_argument("--query", required=True, help="Đường dẫn ảnh nghi phạm cần truy quét")
    parser_search.add_argument("--db", required=True, help="Thư mục chứa kho dữ liệu ảnh dân cư")
    parser_search.add_argument("--top-k", type=int, default=10, help="Số lượng nghi phạm giống nhất hiển thị (Mặc định: 10, Nhập 0 để lấy toàn bộ)")
    parser_search.add_argument("--export", type=str, default=None, help="Xuất báo cáo giám định JSON (Ví dụ: report.json)")
    parser_search.add_argument("--gui", action="store_true", help="Mở giao diện Cyber HUD đồ họa an ninh mạng trực quan")

    # Subcommand: Watch Auto-Sync
    parser_watch = subparsers.add_parser("watch", help="Kích hoạt luồng tự động giám sát thư mục CSDL và nạp đặc trưng ảnh mới")
    parser_watch.add_argument("--db", required=True, help="Thư mục chứa kho dữ liệu ảnh dân cư cần tự động giám sát")
    parser_watch.add_argument("--interval", type=float, default=2.0, help="Tần suất kiểm tra thư mục (Mặc định: 2.0 giây)")

    args = parser.parse_args()

    yunet_path, sface_path = download_models()
    engine = FaceEngine(yunet_path, sface_path)

    if not args.command:
        run_launcher_gui(engine)
        sys.exit(0)

    if args.command == "compare":
        print(f"\n🔍 Đang so sánh đối khớp ảnh:")
        print(f"   └─ Ảnh 1: {args.img1}")
        print(f"   └─ Ảnh 2: {args.img2}")
        
        try:
            v1, face1, landmarks1 = engine.extract_vector(args.img1)
            if v1 is None:
                print("❌ Lỗi: Không tìm thấy khuôn mặt trong Ảnh 1.")
                return
                
            v2, face2, landmarks2 = engine.extract_vector(args.img2)
            if v2 is None:
                print("❌ Lỗi: Không tìm thấy khuôn mặt trong Ảnh 2.")
                return
                
            cosine_sim, l2_dist = FaceEngine.compare_vectors(v1, v2)
            is_match = cosine_sim >= COSINE_THRESHOLD
            
            confidence = 70.0 + (cosine_sim - COSINE_THRESHOLD) / (1.0 - COSINE_THRESHOLD) * 30.0 if is_match else max(0.0, (cosine_sim + 1.0) / (COSINE_THRESHOLD + 1.0) * 70.0)
                
            print("\n================== KẾT QUẢ PHÂN TÍCH VECTOR ==================")
            print(f"📊 Kích thước vector đặc trưng: {v1.shape[1]} chiều (float32)")
            print(f"👉 Độ tương đồng Cosine (Cosine Similarity): {cosine_sim:.4f} (Ngưỡng đạt: >= {COSINE_THRESHOLD})")
            print(f"👉 Khoảng cách L2 (L2 Distance): {l2_dist:.4f} (Ngưỡng đạt: <= {L2_THRESHOLD})")
            print(f"📈 Độ tin cậy khớp danh tính: {confidence:.2f}%")
            print("-------------------------------------------------------------")
            print("🟢 KẾT LUẬN: ĐỐI KHỚP KHỚP (CÙNG MỘT NGƯỜI)" if is_match else "🔴 KẾT LUẬN: KHÔNG ĐỐI KHỚP (KHÁC NGƯỜI)")
            print("=============================================================")
            
        except Exception as e:
            print(f"❌ Có lỗi xảy ra trong quá trình so sánh: {e}")

    elif args.command == "search":
        if not os.path.isdir(args.db):
            print(f"❌ Lỗi: Thư mục CSDL '{args.db}' không tồn tại.")
            sys.exit(1)
            
        cache_file = os.path.join(args.db, "face_signatures_cache.json")
        db_vectors = update_cache_smart(args.db, engine, cache_file)

        if args.gui:
            run_gui_search(engine, args.query, db_vectors, top_k=args.top_k, export_file=args.export)
            return

        # CHẾ ĐỘ TERMINAL (CLI)
        print(f"\n🕵️ Đang truy quét đối tượng:")
        print(f"   └─ Ảnh truy vấn: {args.query}")
        print(f"   └─ Thư mục CSDL: {args.db}")
        
        try:
            q_vector, q_face, q_landmarks = engine.extract_vector(args.query)
            if q_vector is None:
                print("❌ Lỗi: Không tìm thấy khuôn mặt nào trong ảnh truy vấn.")
                return
                
            start_search = time.time()
            results = FaceEngine.batch_compare(q_vector, db_vectors)
            search_duration = time.time() - start_search
            
            print(f"⏱️ Quá trình đối khớp NumPy Vectorized hoàn thành trong {search_duration:.4f} giây.")
            
            display_k = len(results) if (args.top_k <= 0 or args.top_k > len(results)) else args.top_k

            print(f"\n================== TOP {display_k} NGHI PHẠM GIỐNG NHẤT ==================")
            print(f"{'Hạng':<5} | {'Độ tương đồng':<13} | {'Khoảng cách L2':<15} | {'Trạng thái':<12} | {'Đường dẫn ảnh hồ sơ'}")
            print("-" * 100)
            
            for idx, (idx_path, cosine_sim, l2_dist) in enumerate(results[:display_k]):
                is_match = "🟢 ĐỐI KHỚP" if cosine_sim >= COSINE_THRESHOLD else "🔴 KHÁC BIỆT"
                print(f"#{idx+1:<4} | {cosine_sim:.4f} ({cosine_sim*100:6.2f}%) | {l2_dist:<15.4f} | {is_match:<12} | {idx_path}")
            print("====================================================================================")

            if args.export:
                export_report(args.query, db_vectors, results, display_k, search_duration, args.export)
            
        except Exception as e:
            print(f"❌ Có lỗi xảy ra trong quá trình truy quét: {e}")

    elif args.command == "watch":
        if not os.path.isdir(args.db):
            print(f"❌ Lỗi: Thư mục CSDL '{args.db}' không tồn tại.")
            sys.exit(1)

        print("===================================================================")
        print("👁️ HỆ THỐNG TỰ ĐỘNG GIÁM SÁT & NẠP ĐẶC TRƯNG ẢNH MỚI (AUTO-SYNC)")
        print("===================================================================")
        print(f"📁 Thư mục đang theo dõi: {os.path.abspath(args.db)}")
        print(f"⏱️ Tần suất kiểm tra: {args.interval} giây")
        print(">> Bạn có thể chép/thêm bất kỳ ảnh mới nào vào thư mục trên, hệ thống sẽ tự động nạp vector đặc trưng!")
        print(">> Nhấn Ctrl+C để dừng giám sát...\n")

        watcher = DatabaseWatcher(args.db, engine, interval=args.interval)
        watcher.start()

        try:
            while True:
                time.sleep(1)
        except KeyboardInterrupt:
            print("\n🛑 Đã dừng luồng tự động giám sát thư mục.")
            watcher.stop()

if __name__ == "__main__":
    main()
