import os
import sys
import cv2
import numpy as np
import json
import math
import time
import threading
import tkinter as tk
from tkinter import filedialog, messagebox, ttk
from PIL import Image, ImageTk

# Tắt cảnh báo OpenCV DNN
os.environ["OPENCV_LOG_LEVEL"] = "OFF"
try:
    cv2.utils.logging.setLogLevel(cv2.utils.logging.LOG_LEVEL_SILENT)
except Exception:
    pass

# Cấu hình UTF-8 cho Windows Terminal
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass


class FaceComparatorEngine:
    """
    Trích xuất & So sánh Đặc trưng Sinh trắc học Gương mặt 128-D (SFace + YuNet).
    Cung cấp độ tương đồng Cosine & khoảng cách L2 Norm.
    """
    def __init__(self, yunet_path="face_detection_yunet_2023mar.onnx", sface_path="face_recognition_sface_2021dec.onnx"):
        script_dir = os.path.dirname(os.path.abspath(__file__))
        self.yunet_model = os.path.join(script_dir, yunet_path) if not os.path.isabs(yunet_path) else yunet_path
        self.sface_model = os.path.join(script_dir, sface_path) if not os.path.isabs(sface_path) else sface_path

        if os.path.exists(self.yunet_model):
            self.detector = cv2.FaceDetectorYN.create(self.yunet_model, "", (320, 320), 0.35, 0.3, 5000)
        else:
            self.detector = None

        if os.path.exists(self.sface_model):
            self.recognizer = cv2.FaceRecognizerSF.create(self.sface_model, "")
        else:
            self.recognizer = None

    def process_and_extract(self, img_path):
        """Tải ảnh, phát hiện khuôn mặt và trích xuất vector đặc trưng 128-D SFace cùng mốc sinh trắc."""
        if not os.path.exists(img_path):
            return None, "File không tồn tại"

        img = cv2.imread(img_path)
        if img is None:
            return None, "Không thể đọc file ảnh"

        h, w, _ = img.shape
        if self.detector is None or self.recognizer is None:
            return None, "Chưa tải đủ mô hình YuNet / SFace ONNX"

        self.detector.setInputSize((w, h))
        _, faces = self.detector.detect(img)

        # Upscale thử nếu ảnh quá nhỏ
        if (faces is None or len(faces) == 0) and (w < 400 or h < 400):
            resized_img = cv2.resize(img, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)
            self.detector.setInputSize((w * 2, h * 2))
            _, faces = self.detector.detect(resized_img)
            if faces is not None and len(faces) > 0:
                img = resized_img
                h, w, _ = img.shape

        if faces is None or len(faces) == 0:
            return None, "Không phát hiện khuôn mặt nào trong ảnh"

        # Lấy gương mặt có độ phân giải/diện tích lớn nhất
        primary_face = max(faces, key=lambda f: f[2] * f[3])

        box = primary_face[0:4].astype(int)
        landmarks = primary_face[4:14].reshape((5, 2)).astype(int)
        det_score = float(primary_face[14])

        # Căn chỉnh và cắt khuôn mặt (Aligned Crop)
        aligned_crop = self.recognizer.alignCrop(img, primary_face)
        feature_vec = self.recognizer.feature(aligned_crop)
        normalized_feat = cv2.normalize(feature_vec, None)

        # Tính toán hình thái sinh trắc học
        bio_traits = self._compute_biometric_ratios(img, box, landmarks)

        return {
            "image_path": img_path,
            "img_bgr": img,
            "aligned_crop": aligned_crop,
            "bbox": box,
            "landmarks": landmarks,
            "det_score": det_score,
            "feature_raw": feature_vec,
            "feature_norm": normalized_feat,
            "embedding_128d": normalized_feat.flatten().tolist(),
            "biometrics": bio_traits
        }, None

    def _compute_biometric_ratios(self, img, box, landmarks):
        """Tính toán tỷ lệ khoảng cách giữa các mốc mắt, mũi, miệng."""
        x, y, w, h = box
        # Landmarks: [mắt trái, mắt phải, mũi, khóe miệng trái, khóe miệng phải]
        r_eye, l_eye, nose, r_mouth, l_mouth = landmarks

        eye_dist = float(np.linalg.norm(r_eye - l_eye))
        eye_nose_dist = float(np.linalg.norm((r_eye + l_eye) / 2.0 - nose))
        nose_mouth_dist = float(np.linalg.norm(nose - (r_mouth + l_mouth) / 2.0))
        mouth_width = float(np.linalg.norm(r_mouth - l_mouth))

        eye_distance_ratio = round(eye_dist / max(float(w), 1.0), 3)
        nose_mouth_ratio = round(nose_mouth_dist / max(float(h), 1.0), 3)
        face_aspect_ratio = round(float(h) / max(float(w), 1.0), 3)

        return {
            "eye_distance_px": round(eye_dist, 1),
            "eye_distance_ratio": eye_distance_ratio,
            "nose_mouth_ratio": nose_mouth_ratio,
            "mouth_width_px": round(mouth_width, 1),
            "face_aspect_ratio": face_aspect_ratio
        }

    def compute_similarity(self, face_data1, face_data2):
        """Tính toán Cosine Similarity và L2 Distance giữa 2 vector khuôn mặt."""
        feat1 = face_data1["feature_raw"]
        feat2 = face_data2["feature_raw"]

        # Sử dụng API chuẩn của OpenCV FaceRecognizerSF
        cosine_sim = float(self.recognizer.match(feat1, feat2, cv2.FaceRecognizerSF_FR_COSINE))
        l2_dist = float(self.recognizer.match(feat1, feat2, cv2.FaceRecognizerSF_FR_NORM_L2))

        # Quy đổi Cosine Score ra % tương đồng (0% -> 100%)
        match_percentage = min(max(cosine_sim * 100.0, 0.0), 100.0)

        return {
            "cosine_similarity": round(cosine_sim, 4),
            "l2_distance": round(l2_dist, 4),
            "match_percentage": round(match_percentage, 2)
        }


class CyberpunkFaceCompareGUI:
    """Giao diện Cyberpunk HUD So sánh & Xác minh độ tương đồng 2 gương mặt."""
    def __init__(self, root):
        self.root = root
        self.root.title("⚡ CYBER BIOMETRIC ENGINE // FACE SIMILARITY COMPARATOR v3.0")
        self.root.geometry("1280x860")
        self.root.minsize(1100, 720)
        self.root.configure(bg="#08090d")

        self.engine = FaceComparatorEngine()

        self.face_a = None
        self.face_b = None

        self._setup_cyber_theme()
        self._build_ui()

    def _setup_cyber_theme(self):
        self.COLOR_BG = "#08090d"
        self.COLOR_PANEL = "#11131c"
        self.COLOR_BORDER = "#1b2033"
        self.COLOR_CYAN = "#00f3ff"
        self.COLOR_MAGENTA = "#ff0055"
        self.COLOR_GREEN = "#00ff66"
        self.COLOR_YELLOW = "#ffee00"
        self.COLOR_RED = "#ff3333"
        self.COLOR_TEXT = "#e2e8f0"
        self.COLOR_TEXT_DIM = "#64748b"

        self.FONT_TITLE = ("Consolas", 16, "bold")
        self.FONT_HEADER = ("Consolas", 12, "bold")
        self.FONT_MONO = ("Consolas", 10)
        self.FONT_MONO_BOLD = ("Consolas", 10, "bold")
        self.FONT_VERDICT = ("Consolas", 18, "bold")

    def _build_ui(self):
        # 1. Header Bar
        header = tk.Frame(self.root, bg=self.COLOR_PANEL, height=50, bd=1, relief="solid")
        header.pack(fill="x", side="top", padx=8, pady=(8, 4))

        lbl_logo = tk.Label(header, text="⚡ FACE SIMILARITY & IDENTITY VERIFIER", font=self.FONT_TITLE, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_logo.pack(side="left", padx=15)

        lbl_sub = tk.Label(header, text="[SFACE 128-D COSINE MATCHING | BIOMETRIC VERIFICATION]", font=self.FONT_MONO, fg=self.COLOR_MAGENTA, bg=self.COLOR_PANEL)
        lbl_sub.pack(side="right", padx=15)

        # 2. Main Body Split: Top Selection + Controls | Middle Visualizer | Bottom Logs
        body = tk.Frame(self.root, bg=self.COLOR_BG)
        body.pack(fill="both", expand=True, padx=8, pady=4)

        # --- Top Section: Image Selector Cards A & B + Threshold Control Bar ---
        top_frame = tk.Frame(body, bg=self.COLOR_PANEL, bd=1, relief="solid")
        top_frame.pack(fill="x", side="top", pady=(0, 4))

        # Panel Face A
        panel_a = tk.Frame(top_frame, bg="#0d0e17", bd=1, relief="solid")
        panel_a.pack(side="left", fill="both", expand=True, padx=8, pady=8)

        lbl_title_a = tk.Label(panel_a, text="👤 GƯƠNG MẶT A (REFERENCE)", font=self.FONT_HEADER, fg=self.COLOR_CYAN, bg="#0d0e17")
        lbl_title_a.pack(anchor="w", padx=8, pady=4)

        self.btn_load_a = tk.Button(panel_a, text="📂 CHỌN ẢNH A", font=self.FONT_MONO_BOLD, fg=self.COLOR_BG, bg=self.COLOR_CYAN, activebackground=self.COLOR_GREEN, bd=0, pady=4, command=self.load_image_a)
        self.btn_load_a.pack(fill="x", padx=8, pady=2)

        self.lbl_info_a = tk.Label(panel_a, text="Chưa tải ảnh A", font=self.FONT_MONO, fg=self.COLOR_TEXT_DIM, bg="#0d0e17", anchor="w")
        self.lbl_info_a.pack(fill="x", padx=8, pady=4)

        # Center Controls: Threshold & Compare Button
        ctl_center = tk.Frame(top_frame, bg=self.COLOR_PANEL, width=320)
        ctl_center.pack(side="left", fill="y", padx=8, pady=8)

        lbl_thresh_title = tk.Label(ctl_center, text="⚙️ NGƯỠNG XÁC ĐỊNH (THRESHOLD)", font=self.FONT_MONO_BOLD, fg=self.COLOR_YELLOW, bg=self.COLOR_PANEL)
        lbl_thresh_title.pack(anchor="center", pady=(2, 0))

        # Threshold Slider (Mặc định 0.363 - Chuẩn SFace)
        self.slider_thresh = tk.Scale(
            ctl_center, from_=0.10, to=0.80, resolution=0.005, orient="horizontal",
            bg=self.COLOR_PANEL, fg=self.COLOR_CYAN, highlightthickness=0,
            troughcolor="#1a1d2e", activebackground=self.COLOR_GREEN,
            font=self.FONT_MONO_BOLD, command=self.on_threshold_change
        )
        self.slider_thresh.set(0.363)
        self.slider_thresh.pack(fill="x", padx=10, pady=2)

        # Preset Buttons
        preset_frame = tk.Frame(ctl_center, bg=self.COLOR_PANEL)
        preset_frame.pack(pady=2)

        btn_strict = tk.Button(preset_frame, text="Nghiêm ngặt (0.45)", font=("Consolas", 8), fg="#ffffff", bg="#331122", bd=0, padx=4, pady=2, command=lambda: self.slider_thresh.set(0.45))
        btn_strict.pack(side="left", padx=2)

        btn_std = tk.Button(preset_frame, text="Chuẩn SFace (0.363)", font=("Consolas", 8), fg="#ffffff", bg="#113322", bd=0, padx=4, pady=2, command=lambda: self.slider_thresh.set(0.363))
        btn_std.pack(side="left", padx=2)

        btn_relaxed = tk.Button(preset_frame, text="Nới lỏng (0.30)", font=("Consolas", 8), fg="#ffffff", bg="#222233", bd=0, padx=4, pady=2, command=lambda: self.slider_thresh.set(0.30))
        btn_relaxed.pack(side="left", padx=2)

        # Compare Button
        self.btn_compare = tk.Button(ctl_center, text="⚡ SO SÁNH NÉT TƯƠNG ĐỒNG", font=self.FONT_HEADER, fg=self.COLOR_BG, bg=self.COLOR_GREEN, activebackground=self.COLOR_YELLOW, bd=0, pady=6, command=self.run_comparison)
        self.btn_compare.pack(fill="x", padx=10, pady=(6, 2))

        # Panel Face B
        panel_b = tk.Frame(top_frame, bg="#0d0e17", bd=1, relief="solid")
        panel_b.pack(side="right", fill="both", expand=True, padx=8, pady=8)

        lbl_title_b = tk.Label(panel_b, text="👤 GƯƠNG MẶT B (TARGET)", font=self.FONT_HEADER, fg=self.COLOR_MAGENTA, bg="#0d0e17")
        lbl_title_b.pack(anchor="w", padx=8, pady=4)

        self.btn_load_b = tk.Button(panel_b, text="📂 CHỌN ẢNH B", font=self.FONT_MONO_BOLD, fg=self.COLOR_BG, bg=self.COLOR_MAGENTA, activebackground=self.COLOR_YELLOW, bd=0, pady=4, command=self.load_image_b)
        self.btn_load_b.pack(fill="x", padx=8, pady=2)

        self.lbl_info_b = tk.Label(panel_b, text="Chưa tải ảnh B", font=self.FONT_MONO, fg=self.COLOR_TEXT_DIM, bg="#0d0e17", anchor="w")
        self.lbl_info_b.pack(fill="x", padx=8, pady=4)

        # --- Center Section: Cyber Canvas Visualizer & Verdict Banner ---
        self.verdict_frame = tk.Frame(body, bg="#11131c", bd=1, relief="solid")
        self.verdict_frame.pack(fill="x", side="top", pady=4)

        self.lbl_verdict = tk.Label(self.verdict_frame, text="⚡ SẴN SÀNG: VUI LÒNG CHỌN ẢNH A VÀ B ĐỂ SO SÁNH", font=self.FONT_VERDICT, fg=self.COLOR_YELLOW, bg="#11131c")
        self.lbl_verdict.pack(pady=8)

        # Canvas hiển thị ảnh so sánh song song
        self.canvas_frame = tk.Frame(body, bg="#05060a", bd=1, relief="solid")
        self.canvas_frame.pack(fill="both", expand=True, pady=4)

        self.canvas = tk.Canvas(self.canvas_frame, bg="#05060a", highlightthickness=0)
        self.canvas.pack(fill="both", expand=True)

        # --- Bottom Section: Telemetry Console Log ---
        bottom = tk.Frame(self.root, bg=self.COLOR_PANEL, height=140, bd=1, relief="solid")
        bottom.pack(fill="x", side="bottom", padx=8, pady=(4, 8))

        lbl_log_title = tk.Label(bottom, text=">_ BIOMETRIC MATCHING TELEMETRY LOG", font=self.FONT_MONO_BOLD, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_log_title.pack(anchor="w", padx=8, pady=(4, 2))

        self.txt_log = tk.Text(bottom, bg="#050608", fg=self.COLOR_GREEN, font=self.FONT_MONO, height=6, bd=0)
        self.txt_log.pack(fill="both", expand=True, padx=8, pady=(0, 6))
        self.log("Khởi tạo hệ thống so sánh nét tương đồng gương mặt SFace 128-D Sẵn sàng!")

    def log(self, msg):
        timestamp = time.strftime("%H:%M:%S")
        self.txt_log.insert("end", f"[{timestamp}] {msg}\n")
        self.txt_log.see("end")

    def load_image_a(self):
        fpath = filedialog.askopenfilename(title="Chọn ảnh Gương mặt A", filetypes=[("Image Files", "*.jpg *.jpeg *.png *.webp")])
        if fpath:
            self.log(f"Đang xử lý Gương mặt A: {os.path.basename(fpath)}...")
            data, err = self.engine.process_and_extract(fpath)
            if err:
                messagebox.showerror("Lỗi Ảnh A", err)
                self.log(f"❌ Lỗi ảnh A: {err}")
                return
            self.face_a = data
            self.lbl_info_a.config(text=f"✓ {os.path.basename(fpath)} | Matched: {data['det_score']:.2f}", fg=self.COLOR_GREEN)
            self.log(f"✓ Đã trích xuất xong Vector 128-D cho Ảnh A!")
            self.render_visualizer()

    def load_image_b(self):
        fpath = filedialog.askopenfilename(title="Chọn ảnh Gương mặt B", filetypes=[("Image Files", "*.jpg *.jpeg *.png *.webp")])
        if fpath:
            self.log(f"Đang xử lý Gương mặt B: {os.path.basename(fpath)}...")
            data, err = self.engine.process_and_extract(fpath)
            if err:
                messagebox.showerror("Lỗi Ảnh B", err)
                self.log(f"❌ Lỗi ảnh B: {err}")
                return
            self.face_b = data
            self.lbl_info_b.config(text=f"✓ {os.path.basename(fpath)} | Matched: {data['det_score']:.2f}", fg=self.COLOR_GREEN)
            self.log(f"✓ Đã trích xuất xong Vector 128-D cho Ảnh B!")
            self.render_visualizer()

    def on_threshold_change(self, val):
        if self.face_a and self.face_b:
            self.run_comparison()

    def run_comparison(self):
        if not self.face_a or not self.face_b:
            messagebox.showwarning("Cảnh báo", "Vui lòng tải đủ cả Ảnh A và Ảnh B trước khi so sánh!")
            return

        res = self.engine.compute_similarity(self.face_a, self.face_b)
        thresh = float(self.slider_thresh.get())

        cos_sim = res["cosine_similarity"]
        l2_dist = res["l2_distance"]

        # Kết luận dựa trên ngưỡng
        is_same = cos_sim >= thresh

        if is_same:
            verdict_text = f"🟢 XÁC MINH THÀNH CÔNG: CÙNG MỘT ĐỐI TƯỢNG! (Cosine: {cos_sim:.4f} >= Ngưỡng: {thresh:.3f})"
            self.lbl_verdict.config(text=verdict_text, fg=self.COLOR_GREEN)
            self.verdict_frame.config(bg="#052211")
            self.lbl_verdict.config(bg="#052211")
        else:
            verdict_text = f"🔴 XÁC MINH THẤT BẠI: KHÁC ĐỐI TƯỢNG! (Cosine: {cos_sim:.4f} < Ngưỡng: {thresh:.3f})"
            self.lbl_verdict.config(text=verdict_text, fg=self.COLOR_RED)
            self.verdict_frame.config(bg="#2b0808")
            self.lbl_verdict.config(bg="#2b0808")

        # Log chi tiết Telemetry
        self.log(f"==================================================")
        self.log(f"📊 KẾT QUẢ SO SÁNH SINH TRẮC HỌC:")
        self.log(f" • Cosine Similarity Score : {cos_sim:.4f} (Độ tương đồng góc vector)")
        self.log(f" • L2 Norm Distance        : {l2_dist:.4f} (Khoảng cách Euclid)")
        self.log(f" • Ngưỡng cấu hình hiện tại: {thresh:.3f}")
        self.log(f" • Đánh giá cuối cùng       : {'[CÙNG ĐỐI TƯỢNG]' if is_same else '[KHÁC ĐỐI TƯỢNG]'}")

        bio_a = self.face_a["biometrics"]
        bio_b = self.face_b["biometrics"]
        self.log(f" • Tỷ lệ khoảng cách mắt   : Ảnh A={bio_a['eye_distance_ratio']} vs Ảnh B={bio_b['eye_distance_ratio']}")
        self.log(f" • Tỷ lệ Mũi - Miệng       : Ảnh A={bio_a['nose_mouth_ratio']} vs Ảnh B={bio_b['nose_mouth_ratio']}")
        self.log(f"==================================================")

        self.render_visualizer(res, thresh, is_same)

    def render_visualizer(self, sim_res=None, thresh=0.363, is_same=False):
        self.canvas.delete("all")

        cw = self.canvas.winfo_width()
        ch = self.canvas.winfo_height()
        if cw <= 20 or ch <= 20:
            cw, ch = 1000, 450

        # Kích thước khung hiển thị từng khuôn mặt
        box_w = min(int(cw * 0.38), 360)
        box_h = min(int(ch * 0.85), 360)

        # 1. Vẽ Ảnh A bên trái
        pos_a_x = int(cw * 0.1)
        pos_a_y = (ch - box_h) // 2
        if self.face_a:
            img_a = self._draw_face_hud(self.face_a, box_w, box_h, color_bgr=(255, 243, 0)) # Cyan
            self.photo_a = ImageTk.PhotoImage(img_a)
            self.canvas.create_image(pos_a_x, pos_a_y, anchor="nw", image=self.photo_a)
            self.canvas.create_rectangle(pos_a_x, pos_a_y, pos_a_x + box_w, pos_a_y + box_h, outline=self.COLOR_CYAN, width=2)
            self.canvas.create_text(pos_a_x + 10, pos_a_y + 20, text="FACE A // REFERENCE", fill=self.COLOR_CYAN, font=self.FONT_MONO_BOLD, anchor="w")
        else:
            self.canvas.create_rectangle(pos_a_x, pos_a_y, pos_a_x + box_w, pos_a_y + box_h, outline=self.COLOR_BORDER, width=1)
            self.canvas.create_text(pos_a_x + box_w//2, pos_a_y + box_h//2, text="[ CHỜ CHỌN ẢNH A ]", fill=self.COLOR_TEXT_DIM, font=self.FONT_MONO)

        # 2. Vẽ Ảnh B bên phải
        pos_b_x = cw - int(cw * 0.1) - box_w
        pos_b_y = (ch - box_h) // 2
        if self.face_b:
            img_b = self._draw_face_hud(self.face_b, box_w, box_h, color_bgr=(255, 0, 255)) # Magenta
            self.photo_b = ImageTk.PhotoImage(img_b)
            self.canvas.create_image(pos_b_x, pos_b_y, anchor="nw", image=self.photo_b)
            self.canvas.create_rectangle(pos_b_x, pos_b_y, pos_b_x + box_w, pos_b_y + box_h, outline=self.COLOR_MAGENTA, width=2)
            self.canvas.create_text(pos_b_x + 10, pos_b_y + 20, text="FACE B // TARGET", fill=self.COLOR_MAGENTA, font=self.FONT_MONO_BOLD, anchor="w")
        else:
            self.canvas.create_rectangle(pos_b_x, pos_b_y, pos_b_x + box_w, pos_b_y + box_h, outline=self.COLOR_BORDER, width=1)
            self.canvas.create_text(pos_b_x + box_w//2, pos_b_y + box_h//2, text="[ CHỜ CHỌN ẢNH B ]", fill=self.COLOR_TEXT_DIM, font=self.FONT_MONO)

        # 3. Thanh đo tương đồng ở giữa (Central Gauge Bar)
        center_x = cw // 2
        gauge_w = 60
        gauge_h = int(box_h * 0.7)
        gauge_y = pos_a_y + (box_h - gauge_h) // 2

        self.canvas.create_rectangle(center_x - gauge_w//2, gauge_y, center_x + gauge_w//2, gauge_y + gauge_h, outline=self.COLOR_BORDER, fill="#0b0d14", width=2)

        if sim_res:
            cos_score = sim_res["cosine_similarity"]
            # Tiêu chuẩn Cosine từ -0.1 đến 0.8
            norm_val = min(max((cos_score - (-0.1)) / 0.9, 0.0), 1.0)
            fill_h = int(gauge_h * norm_val)

            gauge_color = self.COLOR_GREEN if is_same else self.COLOR_RED
            self.canvas.create_rectangle(center_x - gauge_w//2 + 2, gauge_y + gauge_h - fill_h, center_x + gauge_w//2 - 2, gauge_y + gauge_h - 2, fill=gauge_color, outline="")

            # Vạch kẻ chỉ mốc Ngưỡng Threshold
            thresh_norm = min(max((thresh - (-0.1)) / 0.9, 0.0), 1.0)
            thresh_y = gauge_y + gauge_h - int(gauge_h * thresh_norm)

            self.canvas.create_line(center_x - gauge_w//2 - 15, thresh_y, center_x + gauge_w//2 + 15, thresh_y, fill=self.COLOR_YELLOW, width=2)
            self.canvas.create_text(center_x + gauge_w//2 + 20, thresh_y, text=f"TH: {thresh:.3f}", fill=self.COLOR_YELLOW, font=self.FONT_MONO_BOLD, anchor="w")

            # Đường nối Vector Neon giữa A và B
            line_color = self.COLOR_GREEN if is_same else self.COLOR_RED
            self.canvas.create_line(pos_a_x + box_w, pos_a_y + box_h//2, center_x - gauge_w//2, gauge_y + gauge_h - fill_h, fill=line_color, width=2, dash=(4, 4))
            self.canvas.create_line(center_x + gauge_w//2, gauge_y + gauge_h - fill_h, pos_b_x, pos_b_y + box_h//2, fill=line_color, width=2, dash=(4, 4))

            # Nhãn % tương đồng bên trên
            self.canvas.create_text(center_x, gauge_y - 20, text=f"COSINE: {cos_score:.4f}", fill=gauge_color, font=self.FONT_HEADER, anchor="center")

    def _draw_face_hud(self, face_data, out_w, out_h, color_bgr=(255, 243, 0)):
        """Cắt và vẽ điểm mốc sinh trắc học lên ảnh khuôn mặt."""
        crop = face_data["aligned_crop"].copy()
        ch, cw, _ = crop.shape

        # Scale crop về đúng ô canvas
        scale = min(out_w / cw, out_h / ch)
        nw, nh = int(cw * scale), int(ch * scale)
        resized = cv2.resize(crop, (nw, nh), interpolation=cv2.INTER_CUBIC)

        # Chuyển BGR -> RGB -> PIL Image
        rgb = cv2.cvtColor(resized, cv2.COLOR_BGR2RGB)
        pil_img = Image.fromarray(rgb)

        # Đặt vào background tối
        bg = Image.new("RGB", (out_w, out_h), (8, 9, 13))
        bg.paste(pil_img, ((out_w - nw) // 2, (out_h - nh) // 2))

        return bg


def main():
    root = tk.Tk()
    app = CyberpunkFaceCompareGUI(root)
    root.mainloop()

if __name__ == "__main__":
    main()
