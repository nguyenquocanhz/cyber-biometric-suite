import os
import sys
import cv2
import numpy as np
import json
import argparse
import time
import shutil
import threading
import tkinter as tk
from tkinter import filedialog, messagebox, ttk
from PIL import Image, ImageTk

# Tắt tất cả cảnh báo C++ không cần thiết từ OpenCV DNN
os.environ["OPENCV_LOG_LEVEL"] = "OFF"
try:
    cv2.utils.logging.setLogLevel(cv2.utils.logging.LOG_LEVEL_SILENT)
except AttributeError:
    pass

# Tự động cấu hình mã hóa UTF-8 cho Windows Terminal
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass


class FaceFeatureClassifier:
    """
    Module trích xuất Đặc trưng Sinh trắc học & Phân loại Giới tính Nam/Nữ.
    Tích hợp OpenCV YuNet (Phát hiện mặt & Mốc sinh trắc), SFace (Embedding 128-D).
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

    def extract_facial_biometrics(self, img, face_box, landmarks=None):
        h_img, w_img, _ = img.shape
        x, y, w, h = face_box
        x, y = max(0, x), max(0, y)
        w, h = min(w_img - x, w), min(h_img - y, h)

        if w < 10 or h < 10:
            return None

        crop = img[y:y+h, x:x+w]
        crop_h, crop_w, _ = crop.shape

        gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
        hsv = cv2.cvtColor(crop, cv2.COLOR_BGR2HSV)
        lab = cv2.cvtColor(crop, cv2.COLOR_BGR2LAB)

        # 1. Râu cằm / Dấu vết râu nón ở Nam giới
        chin_region = gray[int(crop_h * 0.70):int(crop_h * 0.98), int(crop_w * 0.2):int(crop_w * 0.8)]
        stubble_variance = float(np.std(chin_region)) if chin_region.size > 0 else 0.0
        chin_brightness = float(np.mean(chin_region)) if chin_region.size > 0 else 128.0

        # 2. Sắc tố đỏ môi & Độ bão hòa màu (Trang điểm Nữ)
        lip_hsv = hsv[int(crop_h * 0.62):int(crop_h * 0.82), int(crop_w * 0.25):int(crop_w * 0.75)]
        lip_lab = lab[int(crop_h * 0.62):int(crop_h * 0.82), int(crop_w * 0.25):int(crop_w * 0.75)]

        lip_saturation = float(np.mean(lip_hsv[:, :, 1])) if lip_hsv.size > 0 else 30.0
        lip_redness = float(np.mean(lip_lab[:, :, 1])) if lip_lab.size > 0 else 128.0

        # 3. Tỷ lệ cằm thon vs Quai hàm vuông
        aspect_ratio = float(crop_h) / max(float(crop_w), 1.0)

        # 4. Độ rậm lông mày
        brow_region = gray[int(crop_h * 0.15):int(crop_h * 0.40), int(crop_w * 0.15):int(crop_w * 0.85)]
        brow_contrast = float(np.std(brow_region)) if brow_region.size > 0 else 0.0

        male_score = 0.50

        if stubble_variance > 28.0 or chin_brightness < 90.0:
            male_score += 0.32
        elif stubble_variance > 18.0:
            male_score += 0.15

        if aspect_ratio <= 1.25:
            male_score += 0.15
        elif aspect_ratio > 1.35:
            male_score -= 0.12

        if lip_redness > 140.0 and lip_saturation > 75.0:
            male_score -= 0.30

        male_score = min(max(male_score, 0.01), 0.99)
        gender = "Male" if male_score >= 0.50 else "Female"
        confidence = male_score if gender == "Male" else (1.0 - male_score)

        return {
            "gender": gender,
            "confidence": round(confidence * 100, 2),
            "male_score": round(male_score, 4),
            "aspect_ratio": round(aspect_ratio, 3),
            "stubble_variance": round(stubble_variance, 2),
            "lip_redness": round(lip_redness, 2),
            "lip_saturation": round(lip_saturation, 2),
            "brow_contrast": round(brow_contrast, 2)
        }

    def process_image(self, img_path):
        img = cv2.imread(img_path)
        if img is None:
            return {"error": f"Không thể đọc hình ảnh: {img_path}"}

        h, w, _ = img.shape
        if self.detector is None:
            return {"error": "Bộ phát hiện YuNet chưa được khởi tạo"}

        self.detector.setInputSize((w, h))
        _, faces = self.detector.detect(img)

        if (faces is None or len(faces) == 0) and (w < 400 or h < 400):
            resized_img = cv2.resize(img, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)
            self.detector.setInputSize((w * 2, h * 2))
            _, faces = self.detector.detect(resized_img)
            if faces is not None and len(faces) > 0:
                img = resized_img
                h, w, _ = img.shape

        results = []
        if faces is not None and len(faces) > 0:
            for face in faces:
                box = face[0:4].astype(int)
                landmarks = face[4:14].reshape((5, 2)).astype(int)
                score = float(face[14])

                bio = self.extract_facial_biometrics(img, box, landmarks)

                embedding_128d = []
                if self.recognizer is not None:
                    aligned_face = self.recognizer.alignCrop(img, face)
                    feat = self.recognizer.feature(aligned_face)
                    feat = cv2.normalize(feat, None).flatten()
                    embedding_128d = feat.tolist()

                face_data = {
                    "bbox": box.tolist(),
                    "landmarks": landmarks.tolist(),
                    "detection_score": round(score, 4),
                    "biometrics": bio,
                    "embedding_128d_sample": embedding_128d[:5] if embedding_128d else []
                }
                results.append(face_data)

        genders = [f["biometrics"]["gender"] for f in results if f["biometrics"]]
        num_males = genders.count("Male")
        num_females = genders.count("Female")

        if num_males > 0 and num_females > 0:
            overall_category = "nam_va_nu"
        elif num_females > 0 and num_males == 0:
            overall_category = "only_nu"
        elif num_males > 0 and num_females == 0:
            overall_category = "only_nam"
        else:
            overall_category = "khong_ro_mat"

        return {
            "image_path": os.path.abspath(img_path),
            "dimensions": [w, h],
            "total_faces_detected": len(results),
            "overall_category": overall_category,
            "male_count": num_males,
            "female_count": num_females,
            "faces": results
        }


# ==============================================================================
# GIAO DIỆN ĐỒ HỌA CYBERPUNK HUD GUI (TKINTER)
# ==============================================================================
class CyberpunkFaceGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("⚡ CYBER BIOMETRIC ENGINE // FACE FEATURE CLASSIFIER v3.0")
        self.root.geometry("1200x800")
        self.root.minsize(1000, 650)
        self.root.configure(bg="#08090d")

        self.classifier = FaceFeatureClassifier()
        self.current_img_path = None
        self.processed_data = None
        self.current_pil_img = None

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
        self.COLOR_TEXT = "#e2e8f0"
        self.COLOR_TEXT_DIM = "#64748b"

        self.FONT_HEADER = ("Consolas", 14, "bold")
        self.FONT_TITLE = ("Consolas", 18, "bold")
        self.FONT_MONO = ("Consolas", 10)
        self.FONT_MONO_BOLD = ("Consolas", 10, "bold")

    def _build_ui(self):
        # 1. Header Bar
        header = tk.Frame(self.root, bg=self.COLOR_PANEL, height=50, bd=1, relief="solid")
        header.pack(fill="x", side="top", padx=8, pady=(8, 4))

        lbl_logo = tk.Label(header, text="⚡ CYBER BIOMETRIC ENGINE", font=self.FONT_TITLE, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_logo.pack(side="left", padx=15)

        lbl_sub = tk.Label(header, text="[SYSTEM ONLINE // YUNET 2023 | SFACE 128-D | NEON HUD]", font=self.FONT_MONO, fg=self.COLOR_MAGENTA, bg=self.COLOR_PANEL)
        lbl_sub.pack(side="right", padx=15)

        # 2. Main Body Split Container
        body = tk.Frame(self.root, bg=self.COLOR_BG)
        body.pack(fill="both", expand=True, padx=8, pady=4)

        # Left Control Panel
        left_panel = tk.Frame(body, bg=self.COLOR_PANEL, width=380, bd=1, relief="solid")
        left_panel.pack(side="left", fill="y", padx=(0, 4))
        left_panel.pack_propagate(False)

        # Right HUD Display Frame
        right_panel = tk.Frame(body, bg=self.COLOR_PANEL, bd=1, relief="solid")
        right_panel.pack(side="right", fill="both", expand=True)

        self._build_left_controls(left_panel)
        self._build_right_hud(right_panel)

        # 3. Bottom Terminal Log Console
        bottom = tk.Frame(self.root, bg=self.COLOR_PANEL, height=120, bd=1, relief="solid")
        bottom.pack(fill="x", side="bottom", padx=8, pady=(4, 8))

        lbl_log_title = tk.Label(bottom, text=">_ TELEMETRY TERMINAL CONSOLE", font=self.FONT_MONO_BOLD, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_log_title.pack(anchor="w", padx=8, pady=(4, 2))

        self.txt_log = tk.Text(bottom, bg="#050608", fg=self.COLOR_GREEN, font=self.FONT_MONO, height=5, bd=0)
        self.txt_log.pack(fill="both", expand=True, padx=8, pady=(0, 6))
        self.log("Khởi tạo thành công Cyber Biometric HUD Engine. Sẵn sàng xử lý!")

    def _build_left_controls(self, parent):
        lbl_sec1 = tk.Label(parent, text="[ CONTROLS & OPERATIONS ]", font=self.FONT_HEADER, fg=self.COLOR_YELLOW, bg=self.COLOR_PANEL)
        lbl_sec1.pack(anchor="w", padx=12, pady=(12, 6))

        # Buttons Frame
        btn_frame = tk.Frame(parent, bg=self.COLOR_PANEL)
        btn_frame.pack(fill="x", padx=12, pady=4)

        btn_browse_img = tk.Button(btn_frame, text="🖼️ BROWSE IMAGE (XEM 1 ẢNH)", font=self.FONT_MONO_BOLD, fg=self.COLOR_BG, bg=self.COLOR_CYAN, activebackground=self.COLOR_GREEN, bd=0, py=6, command=self.browse_single_image)
        btn_browse_img.pack(fill="x", pady=4)

        btn_browse_dir = tk.Button(btn_frame, text="📂 BROWSE DATASET (PHÂN LOẠI KHÔ)", font=self.FONT_MONO_BOLD, fg=self.COLOR_BG, bg=self.COLOR_MAGENTA, activebackground=self.COLOR_YELLOW, bd=0, py=6, command=self.browse_dataset_dir)
        btn_browse_dir.pack(fill="x", pady=4)

        # Telemetry Card Frame
        lbl_sec2 = tk.Label(parent, text="[ BIOMETRIC TELEMETRY ]", font=self.FONT_HEADER, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_sec2.pack(anchor="w", padx=12, pady=(16, 6))

        self.card_info = tk.Frame(parent, bg="#0d0e17", bd=1, relief="solid")
        self.card_info.pack(fill="both", expand=True, padx=12, pady=(0, 12))

        self.lbl_status = tk.Label(self.card_info, text="STATUS: IDLE", font=self.FONT_MONO_BOLD, fg=self.COLOR_TEXT_DIM, bg="#0d0e17")
        self.lbl_status.pack(anchor="w", padx=10, pady=(10, 4))

        self.lbl_category = tk.Label(self.card_info, text="CATEGORY: --", font=self.FONT_MONO_BOLD, fg=self.COLOR_YELLOW, bg="#0d0e17")
        self.lbl_category.pack(anchor="w", padx=10, pady=2)

        self.lbl_faces_cnt = tk.Label(self.card_info, text="FACES DETECTED: 0", font=self.FONT_MONO, fg=self.COLOR_TEXT, bg="#0d0e17")
        self.lbl_faces_cnt.pack(anchor="w", padx=10, pady=2)

        self.lbl_confidence = tk.Label(self.card_info, text="GENDER CONFIDENCE: --", font=self.FONT_MONO, fg=self.COLOR_GREEN, bg="#0d0e17")
        self.lbl_confidence.pack(anchor="w", padx=10, pady=2)

        # Details Text area inside Telemetry Card
        self.txt_details = tk.Text(self.card_info, bg="#07080d", fg=self.COLOR_TEXT, font=self.FONT_MONO, bd=0)
        self.txt_details.pack(fill="both", expand=True, padx=10, pady=10)

    def _build_right_hud(self, parent):
        lbl_hud_header = tk.Label(parent, text="[ CYBER HUD VISUALIZER CANVAS ]", font=self.FONT_HEADER, fg=self.COLOR_MAGENTA, bg=self.COLOR_PANEL)
        lbl_hud_header.pack(anchor="w", padx=12, pady=(10, 4))

        self.canvas_frame = tk.Frame(parent, bg="#05060a", bd=1, relief="solid")
        self.canvas_frame.pack(fill="both", expand=True, padx=10, pady=(0, 10))

        self.canvas = tk.Canvas(self.canvas_frame, bg="#05060a", highlightthickness=0)
        self.canvas.pack(fill="both", expand=True)
        self.canvas.bind("<Configure>", self.on_canvas_resize)

    def log(self, msg):
        timestamp = time.strftime("%H:%M:%S")
        self.txt_log.insert("end", f"[{timestamp}] {msg}\n")
        self.txt_log.see("end")

    def browse_single_image(self):
        fpath = filedialog.askopenfilename(title="Chọn ảnh sinh trắc học", filetypes=[("Image Files", "*.jpg *.jpeg *.png *.webp")])
        if fpath:
            self.current_img_path = fpath
            self.log(f"Đã mở tệp ảnh: {os.path.basename(fpath)}")
            self.run_single_image_analysis()

    def run_single_image_analysis(self):
        if not self.current_img_path:
            return

        self.log("Đang phân tích mốc sinh trắc & vector 128-D SFace...")
        data = self.classifier.process_image(self.current_img_path)
        self.processed_data = data

        num_faces = data.get("total_faces_detected", 0)
        cat = data.get("overall_category", "khong_ro_mat")
        males = data.get("male_count", 0)
        females = data.get("female_count", 0)

        # Cập nhật Telemetry Card
        self.lbl_status.config(text="STATUS: 🟢 ANALYSIS COMPLETE", fg=self.COLOR_GREEN)
        self.lbl_category.config(text=f"CATEGORY: {cat.upper()}", fg=self.COLOR_YELLOW)
        self.lbl_faces_cnt.config(text=f"FACES DETECTED: {num_faces} ({males} Nam, {females} Nữ)", fg=self.COLOR_CYAN)

        self.txt_details.delete("1.0", "end")
        self.txt_details.insert("end", f"▶ FILE: {os.path.basename(self.current_img_path)}\n")
        self.txt_details.insert("end", f"▶ DIMENSIONS: {data.get('dimensions')}\n\n")

        for idx, f in enumerate(data.get("faces", []), 1):
            bio = f.get("biometrics", {})
            g = bio.get("gender", "Unknown")
            c = bio.get("confidence", 0)
            emb = f.get("embedding_128d_sample", [])

            self.txt_details.insert("end", f"--- FACE #{idx} --- \n")
            self.txt_details.insert("end", f" • Verdict  : {g} ({c}%)\n")
            self.txt_details.insert("end", f" • AspectRatio: {bio.get('aspect_ratio')}\n")
            self.txt_details.insert("end", f" • StubbleVar : {bio.get('stubble_variance')}\n")
            self.txt_details.insert("end", f" • LipRedness : {bio.get('lip_redness')}\n")
            self.txt_details.insert("end", f" • 128-D Sample: {emb[:3]}...\n\n")

        self.draw_cyber_hud_image()

    def draw_cyber_hud_image(self):
        if not self.current_img_path:
            return

        img = cv2.imread(self.current_img_path)
        if img is None:
            return

        h, w, _ = img.shape

        # Vẽ HUD overlays trên ảnh OpenCV
        if self.processed_data and "faces" in self.processed_data:
            for f in self.processed_data["faces"]:
                box = f["bbox"]
                landmarks = f["landmarks"]
                bio = f["biometrics"]
                gender = bio.get("gender", "Unknown")
                conf = bio.get("confidence", 0)

                color = (255, 0, 255) if gender == "Female" else (255, 243, 0) # BGR: Magenta vs Cyan
                x, y, bw, bh = box

                # Khung chữ nhật Cyber Neon
                cv2.rectangle(img, (x, y), (x + bw, y + bh), color, 2)

                # Vẽ 5 điểm mốc Landmark (Mắt, Mũi, Miệng)
                for l_idx, (lx, ly) in enumerate(landmarks):
                    cv2.circle(img, (lx, ly), 4, (0, 255, 0), -1)

                # Nhãn danh tính & Độ tin cậy
                lbl = f"{gender.upper()} {conf:.0f}%"
                cv2.putText(img, lbl, (x, max(15, y - 8)), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)

        # Chuyển OpenCV (BGR) -> PIL (RGB)
        img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        self.current_pil_img = Image.fromarray(img_rgb)
        self.render_canvas_image()

    def render_canvas_image(self):
        if not self.current_pil_img:
            return

        cw = self.canvas.winfo_width()
        ch = self.canvas.winfo_height()

        if cw <= 10 or ch <= 10:
            return

        img_w, img_h = self.current_pil_img.size
        ratio = min(cw / img_w, ch / img_h)

        new_w = int(img_w * ratio)
        new_h = int(img_h * ratio)

        resized = self.current_pil_img.resize((new_w, new_h), Image.Resampling.LANCZOS)
        self.tk_photo = ImageTk.PhotoImage(resized)

        self.canvas.delete("all")
        # Canh giữa Canvas
        pos_x = (cw - new_w) // 2
        pos_y = (ch - new_h) // 2
        self.canvas.create_image(pos_x, pos_y, anchor="nw", image=self.tk_photo)

        # Vẽ lưới Cyber Grid phủ lên Canvas
        self.canvas.create_rectangle(pos_x, pos_y, pos_x + new_w, pos_y + new_h, outline=self.COLOR_CYAN, width=1)
        self.canvas.create_text(pos_x + 10, pos_y + 15, text="CYBER HUD // LIVE TELEMETRY", fill=self.COLOR_CYAN, font=self.FONT_MONO, anchor="w")

    def on_canvas_resize(self, event):
        self.render_canvas_image()

    def browse_dataset_dir(self):
        dpath = filedialog.askdirectory(title="Chọn Thư mục CSDL để Phân loại Hàng loạt")
        if dpath:
            self.log(f"Bắt đầu luồng phân loại hàng loạt cho thư mục: {dpath}")
            threading.Thread(target=self._run_batch_classification_thread, args=(dpath,), daemon=True).start()

    def _run_batch_classification_thread(self, dpath):
        from classify_gender import classify_and_sort_dataset
        self.lbl_status.config(text="STATUS: 🟡 BATCH PROCESSING...", fg=self.COLOR_YELLOW)
        classify_and_sort_dataset(dpath)
        self.lbl_status.config(text="STATUS: 🟢 BATCH FINISHED", fg=self.COLOR_GREEN)
        self.log(f"✨ Hoàn tất phân loại hàng loạt cho thư mục '{dpath}'! Báo cáo JSON đã xuất.")
        messagebox.showinfo("CYBER BIOMETRIC ENGINE", f"Đã phân loại xong toàn bộ ảnh trong '{dpath}'!")


# ==============================================================================
# MAIN ENTRY POINT
# ==============================================================================
def main():
    parser = argparse.ArgumentParser(description="Trích xuất Đặc trưng Sinh trắc học & Phân loại Giới tính Nam/Nữ")
    parser.add_argument("--img", type=str, help="Đường dẫn đến file ảnh cần trích xuất")
    parser.add_argument("--dir", type=str, help="Đường dẫn đến thư mục chứa danh sách ảnh")
    parser.add_argument("--export", type=str, default="face_classification_export.json", help="File JSON lưu báo cáo")
    parser.add_argument("--gui", action="store_true", help="Khởi chạy Giao diện Cyberpunk HUD GUI")

    args = parser.parse_args()

    # Nếu không truyền tham số CLI hoặc có --gui thì bật Cyberpunk GUI
    if args.gui or (not args.img and not args.dir):
        root = tk.Tk()
        app = CyberpunkFaceGUI(root)
        root.mainloop()
    elif args.img:
        classifier = FaceFeatureClassifier()
        res = classifier.process_image(args.img)
        print(json.dumps(res, indent=2, ensure_ascii=False))
    elif args.dir:
        classifier = FaceFeatureClassifier()
        valid_exts = {".jpg", ".jpeg", ".png", ".webp"}
        files = [f for f in os.listdir(args.dir) if os.path.splitext(f.lower())[1] in valid_exts]
        print(f"🚀 Đang trích xuất đặc trưng cho {len(files)} hình ảnh...")
        all_res = []
        for idx, f in enumerate(files, 1):
            fpath = os.path.join(args.dir, f)
            out = classifier.process_image(fpath)
            all_res.append(out)
        with open(args.export, "w", encoding="utf-8") as f:
            json.dump(all_res, f, indent=2, ensure_ascii=False)
        print(f"✨ Đã xuất báo cáo tại: {os.path.abspath(args.export)}")

if __name__ == "__main__":
    main()
