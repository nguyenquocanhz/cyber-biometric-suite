import os
import sys
import cv2
import numpy as np
import json
import time
import argparse
import threading
import tkinter as tk
from tkinter import filedialog, messagebox, ttk
from PIL import Image, ImageTk

# Tắt tất cả cảnh báo C++ từ OpenCV DNN
os.environ["OPENCV_LOG_LEVEL"] = "OFF"
try:
    cv2.utils.logging.setLogLevel(cv2.utils.logging.LOG_LEVEL_SILENT)
except Exception:
    pass

# Cấu hình UTF-8 cho Terminal Windows
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass


class MultiFaceIDGallery:
    """
    Hệ thống Quản lý & Gom nhóm Kho nhận dạng FaceID (1:N Face Recognition Gallery).
    Quét tự động toàn bộ thư mục CSDL, đánh mã FaceID và lưu trữ Vector 128-D.
    """
    def __init__(self, yunet_path="face_detection_yunet_2023mar.onnx", sface_path="face_recognition_sface_2021dec.onnx"):
        script_dir = os.path.dirname(os.path.abspath(__file__))
        self.yunet_model = os.path.join(script_dir, yunet_path) if not os.path.isabs(yunet_path) else yunet_path
        self.sface_model = os.path.join(script_dir, sface_path) if not os.path.isabs(sface_path) else sface_path
        self.gallery_file = os.path.join(script_dir, "face_id_gallery.json")

        try:
            from gpu_accelerator import create_gpu_face_engine
            self.detector, self.recognizer, self.gpu_engine = create_gpu_face_engine(self.yunet_model, self.sface_model)
        except Exception:
            if os.path.exists(self.yunet_model):
                self.detector = cv2.FaceDetectorYN.create(self.yunet_model, "", (320, 320), 0.20, 0.3, 10000)
            else:
                self.detector = None

            if os.path.exists(self.sface_model):
                self.recognizer = cv2.FaceRecognizerSF.create(self.sface_model, "")
            else:
                self.recognizer = None

        self.face_db = []  # Danh sách mẫu: [{"face_id": "FID_001", "name": "...", "path": "...", "vector": [...]}]
        self.load_gallery_cache()

    def load_gallery_cache(self):
        if os.path.exists(self.gallery_file):
            try:
                with open(self.gallery_file, "r", encoding="utf-8") as f:
                    self.face_db = json.load(f)
                print(f"📖 Đã nạp kho FaceID gồm {len(self.face_db)} mẫu nhận dạng từ tệp cache.")
            except Exception:
                self.face_db = []

    def save_gallery_cache(self):
        with open(self.gallery_file, "w", encoding="utf-8") as f:
            json.dump(self.face_db, f, indent=2, ensure_ascii=False)
        print(f"💾 Đã xuất chỉ mục CSDL FaceID ({len(self.face_db)} bản ghi) ra {self.gallery_file}")

    def build_or_update_gallery(self, target_folders=None, force_rebuild=False):
        """
        Quét tất cả các thư mục chứa ảnh trong kho dữ liệu,
        phát hiện mặt, trích xuất 128-D SFace vector và tự động gom nhóm đánh mã FaceID.
        """
        script_dir = os.path.dirname(os.path.abspath(__file__))
        if force_rebuild:
            print("🔄 Thực hiện làm mới & quét lại từ đầu (Force Rebuild) toàn bộ chỉ mục...")
            self.face_db = []

        if not target_folders:
            exclude_dirs = {".git", "vendor", "node_modules", ".gemini", "__pycache__"}
            target_folders = [script_dir]
            for item in os.listdir(script_dir):
                full_p = os.path.join(script_dir, item)
                if os.path.isdir(full_p) and item not in exclude_dirs:
                    target_folders.append(full_p)

        valid_exts = {".jpg", ".jpeg", ".png", ".webp"}
        new_records = []
        existing_paths = {item["path"] for item in self.face_db}

        print("\n🔍 BẮT ĐẦU QUÉT & TẠO CHỈ MỤC KHO FACEID ĐA ĐỐI TƯỢNG (GIỚI HẠN MỞ RỘNG)...")

        counter = len(self.face_db) + 1

        for folder in target_folders:
            if not os.path.exists(folder):
                continue

            folder_name = os.path.basename(folder)
            files = [os.path.join(root, file)
                     for root, _, fs in os.walk(folder)
                     for file in fs if os.path.splitext(file.lower())[1] in valid_exts]

            print(f"📁 Thư mục '{folder_name}': Phát hiện {len(files)} tệp ảnh.")

            for fpath in files:
                abs_path = os.path.abspath(fpath)
                if abs_path in existing_paths:
                    continue

                img = cv2.imread(abs_path)
                if img is None:
                    continue

                h, w, _ = img.shape
                self.detector.setInputSize((w, h))
                _, faces = self.detector.detect(img)

                # Upscale 2x thử phát hiện khuôn mặt nhỏ/xa nếu chưa thấy
                if (faces is None or len(faces) == 0) and (w < 600 or h < 600):
                    resized_img = cv2.resize(img, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)
                    self.detector.setInputSize((w * 2, h * 2))
                    _, faces = self.detector.detect(resized_img)
                    if faces is not None and len(faces) > 0:
                        img = resized_img
                        h, w, _ = img.shape

                if faces is None or len(faces) == 0:
                    continue

                for f_idx, face in enumerate(faces, 1):
                    try:
                        aligned = self.recognizer.alignCrop(img, face)
                        feat = self.recognizer.feature(aligned)
                        norm_feat = cv2.normalize(feat, None).flatten().tolist()
                    except Exception:
                        continue

                    face_id_tag = f"FID_{counter:04d}"

                    rec = {
                        "face_id": face_id_tag,
                        "folder": folder_name,
                        "filename": os.path.basename(abs_path),
                        "path": abs_path,
                        "face_index": f_idx,
                        "vector": norm_feat
                    }
                    new_records.append(rec)
                    existing_paths.add(abs_path)
                    counter += 1

        if new_records:
            self.face_db.extend(new_records)
            self.save_gallery_cache()
            print(f"✨ Đã mở rộng thêm mới {len(new_records)} bản ghi FaceID vào kho dữ liệu!")
        else:
            print("✓ Kho FaceID đã ở trạng thái đồng bộ mới nhất.")

        return len(self.face_db)

    def identify_face_1_to_n(self, query_img_path, top_k=5, threshold=0.363):
        """
        Nhận dạng 1:N (Query Face vs Kho CSDL FaceID).
        Trả về danh sách đối khớp phù hợp nhất xếp theo Cosine Similarity.
        """
        if not self.face_db:
            return None, "Kho FaceID CSDL đang rỗng. Vui lòng tạo chỉ mục trước!"

        img = cv2.imread(query_img_path)
        if img is None:
            return None, "Không thể đọc tệp ảnh cần quét nhận dạng"

        h, w, _ = img.shape
        self.detector.setInputSize((w, h))
        _, faces = self.detector.detect(img)

        if faces is None or len(faces) == 0:
            return None, "Không phát hiện khuôn mặt nào trong ảnh quét"

        # Lấy khuôn mặt chính (diện tích lớn nhất)
        primary_face = max(faces, key=lambda f: f[2] * f[3])
        aligned = self.recognizer.alignCrop(img, primary_face)
        query_feat = self.recognizer.feature(aligned)
        query_feat = cv2.normalize(query_feat, None).flatten()

        # Ma trận hóa toàn bộ CSDL để tính Cosine Similarity cực nhanh
        db_vectors = np.array([item["vector"] for item in self.face_db], dtype=np.float32)

        # Cosine Similarity: S = Q @ DB.T
        cosine_scores = np.dot(db_vectors, query_feat)

        matches = []
        for idx, score in enumerate(cosine_scores):
            rec = self.face_db[idx]
            match_pct = min(max(float(score) * 100.0, 0.0), 100.0)
            matches.append({
                "face_id": rec["face_id"],
                "filename": rec["filename"],
                "folder": rec["folder"],
                "path": rec["path"],
                "cosine_sim": round(float(score), 4),
                "match_percentage": round(match_pct, 2),
                "is_same_identity": float(score) >= threshold
            })

        # Sắp xếp giảm dần theo Cosine Score
        matches.sort(key=lambda x: x["cosine_sim"], reverse=True)
        top_matches = matches[:top_k]

        best_match = top_matches[0] if top_matches else None
        identified = best_match["is_same_identity"] if best_match else False

        return {
            "query_image": os.path.abspath(query_img_path),
            "total_gallery_faces": len(self.face_db),
            "identified": identified,
            "threshold": threshold,
            "best_match": best_match,
            "top_k_matches": top_matches
        }, None


class CyberpunkFaceIDScannerGUI:
    """Giao diện Cyberpunk HUD Quét Nhận dạng FaceID 1:N trong kho CSDL."""
    def __init__(self, root):
        self.root = root
        self.root.title("⚡ CYBER BIOMETRIC ENGINE // 1:N FACEID IDENTIFICATION SCANNER v3.0")
        self.root.geometry("1280x880")
        self.root.minsize(1100, 750)
        self.root.configure(bg="#08090d")

        self.gallery = MultiFaceIDGallery()

        self.query_data = None
        self.scan_results = None

        self._setup_cyber_theme()
        self._build_ui()

    def _setup_cyber_theme(self):
        self.COLOR_BG = "#08090d"
        self.COLOR_PANEL = "#11131c"
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

    def _build_ui(self):
        # Header
        header = tk.Frame(self.root, bg=self.COLOR_PANEL, height=50, bd=1, relief="solid")
        header.pack(fill="x", side="top", padx=8, pady=(8, 4))

        lbl_logo = tk.Label(header, text="⚡ 1:N FACEID RECOGNITION SCANNER", font=self.FONT_TITLE, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_logo.pack(side="left", padx=15)

        self.lbl_count = tk.Label(header, text=f"[KHO CSDL: {len(self.gallery.face_db)} FACEIDS INDEXED]", font=self.FONT_MONO, fg=self.COLOR_YELLOW, bg=self.COLOR_PANEL)
        self.lbl_count.pack(side="right", padx=15)

        # Body Split
        body = tk.Frame(self.root, bg=self.COLOR_BG)
        body.pack(fill="both", expand=True, padx=8, pady=4)

        # Controls & Top Bar
        top_bar = tk.Frame(body, bg=self.COLOR_PANEL, bd=1, relief="solid")
        top_bar.pack(fill="x", side="top", pady=(0, 4))

        btn_frame = tk.Frame(top_bar, bg=self.COLOR_PANEL)
        btn_frame.pack(side="left", padx=10, pady=8)

        btn_scan = tk.Button(btn_frame, text="🔍 CHỌN ẢNH CẦN QUÉT NHẬN DẠNG", font=self.FONT_HEADER, fg=self.COLOR_BG, bg=self.COLOR_CYAN, activebackground=self.COLOR_GREEN, bd=0, padx=10, pady=6, command=self.scan_query_image)
        btn_scan.pack(side="left", padx=5)

        btn_sync = tk.Button(btn_frame, text="🔄 TỰ ĐỘNG CẬP NHẬT KHO FACEID", font=self.FONT_HEADER, fg=self.COLOR_BG, bg=self.COLOR_YELLOW, activebackground=self.COLOR_GREEN, bd=0, padx=10, pady=6, command=self.sync_gallery)
        btn_sync.pack(side="left", padx=5)

        # Threshold Slider
        thresh_frame = tk.Frame(top_bar, bg=self.COLOR_PANEL)
        thresh_frame.pack(side="right", padx=15, pady=4)

        lbl_tr = tk.Label(thresh_frame, text="Ngưỡng Nhận Dạng:", font=self.FONT_MONO_BOLD, fg=self.COLOR_TEXT, bg=self.COLOR_PANEL)
        lbl_tr.pack(side="left", padx=4)

        self.slider_tr = tk.Scale(thresh_frame, from_=0.20, to=0.70, resolution=0.01, orient="horizontal", bg=self.COLOR_PANEL, fg=self.COLOR_CYAN, highlightthickness=0, troughcolor="#1a1d2e", length=140, command=self.on_thresh_change)
        self.slider_tr.set(0.363)
        self.slider_tr.pack(side="left", padx=4)

        # Verdict Frame
        self.verdict_frame = tk.Frame(body, bg="#11131c", bd=1, relief="solid")
        self.verdict_frame.pack(fill="x", side="top", pady=4)

        self.lbl_verdict = tk.Label(self.verdict_frame, text="⚡ SẴN SÀNG QUÉT: CHỌN ẢNH ĐỂ XÁC ĐỊNH DANH TÍNH TRONG KHO DATASET", font=self.FONT_HEADER, fg=self.COLOR_YELLOW, bg="#11131c")
        self.lbl_verdict.pack(pady=8)

        # Display Split: Left Query Preview | Right Top-K Match List
        disp_frame = tk.Frame(body, bg=self.COLOR_BG)
        disp_frame.pack(fill="both", expand=True, pady=4)

        # Left Canvas: Query Image HUD
        left_box = tk.Frame(disp_frame, bg="#05060a", bd=1, relief="solid")
        left_box.pack(side="left", fill="both", expand=True, padx=(0, 4))

        lbl_l = tk.Label(left_box, text="[ ẢNH CẦN NHẬN DẠNG (QUERY FACE) ]", font=self.FONT_MONO_BOLD, fg=self.COLOR_CYAN, bg="#05060a")
        lbl_l.pack(anchor="w", padx=8, pady=4)

        self.canvas_query = tk.Canvas(left_box, bg="#05060a", highlightthickness=0)
        self.canvas_query.pack(fill="both", expand=True, padx=4, pady=4)

        # Right Frame: Top-K Matches Table / Treeview
        right_box = tk.Frame(disp_frame, bg=self.COLOR_PANEL, bd=1, relief="solid", width=540)
        right_box.pack(side="right", fill="both", expand=False, padx=(4, 0))
        right_box.pack_propagate(False)

        lbl_r = tk.Label(right_box, text="[ TOP MATCHING FACEIDS IN DATASET ]", font=self.FONT_MONO_BOLD, fg=self.COLOR_MAGENTA, bg=self.COLOR_PANEL)
        lbl_r.pack(anchor="w", padx=8, pady=4)

        # Treeview danh sách match
        cols = ("rank", "face_id", "folder", "filename", "cosine", "status")
        self.tree = ttk.Treeview(right_box, columns=cols, show="headings", height=12)
        self.tree.heading("rank", text="#")
        self.tree.heading("face_id", text="FaceID")
        self.tree.heading("folder", text="Thư mục")
        self.tree.heading("filename", text="Tệp gốc")
        self.tree.heading("cosine", text="Cosine")
        self.tree.heading("status", text="Xác nhận")

        self.tree.column("rank", width=30, anchor="center")
        self.tree.column("face_id", width=80, anchor="center")
        self.tree.column("folder", width=90, anchor="center")
        self.tree.column("filename", width=140, anchor="w")
        self.tree.column("cosine", width=70, anchor="center")
        self.tree.column("status", width=90, anchor="center")

        self.tree.pack(fill="both", expand=True, padx=8, pady=4)
        self.tree.bind("<<TreeviewSelect>>", self.on_tree_select)

        # Bottom Log Terminal
        bottom = tk.Frame(self.root, bg=self.COLOR_PANEL, height=130, bd=1, relief="solid")
        bottom.pack(fill="x", side="bottom", padx=8, pady=(4, 8))

        lbl_log = tk.Label(bottom, text=">_ 1:N FACE RECOGNITION TELEMETRY LOG", font=self.FONT_MONO_BOLD, fg=self.COLOR_CYAN, bg=self.COLOR_PANEL)
        lbl_log.pack(anchor="w", padx=8, pady=(4, 2))

        self.txt_log = tk.Text(bottom, bg="#050608", fg=self.COLOR_GREEN, font=self.FONT_MONO, height=5, bd=0)
        self.txt_log.pack(fill="both", expand=True, padx=8, pady=(0, 6))
        self.log(f"Khởi chạy thành công 1:N FaceID Scanner. Kho hiện tại: {len(self.gallery.face_db)} mẫu.")

    def log(self, msg):
        ts = time.strftime("%H:%M:%S")
        self.txt_log.insert("end", f"[{ts}] {msg}\n")
        self.txt_log.see("end")

    def sync_gallery(self):
        def _worker():
            self.log("🚀 Bắt đầu quét & tự động cập nhật kho chỉ mục FaceID...")
            cnt = self.gallery.build_or_update_gallery()
            self.lbl_count.config(text=f"[KHO CSDL: {cnt} FACEIDS INDEXED]")
            self.log(f"✨ Cập nhật kho FaceID thành công! Tổng số mẫu: {cnt}")
            messagebox.showinfo("FACEID GALLERY", f"Đã đồng bộ xong kho dữ liệu! Tổng số: {cnt} FaceIDs.")

        threading.Thread(target=_worker, daemon=True).start()

    def scan_query_image(self):
        fpath = filedialog.askopenfilename(title="Chọn ảnh cần quét danh tính trong CSDL", filetypes=[("Image Files", "*.jpg *.jpeg *.png *.webp")])
        if fpath:
            self.current_query_path = fpath
            self.run_1_to_n_search()

    def on_thresh_change(self, val):
        if hasattr(self, 'current_query_path') and self.current_query_path:
            self.run_1_to_n_search()

    def on_tree_select(self, event):
        selected_items = self.tree.selection()
        if not selected_items or not self.scan_results:
            return
        item_id = selected_items[0]
        values = self.tree.item(item_id, "values")
        if not values:
            return

        rank_idx = int(values[0]) - 1
        matches = self.scan_results.get("top_k_matches", [])
        if 0 <= rank_idx < len(matches):
            selected_match = matches[rank_idx]
            self.log(f"👁️ Chọn đối chiếu mẫu #{rank_idx + 1}: {selected_match['face_id']} ({selected_match['filename']})")
            self.render_query_canvas(selected_match)

    def run_1_to_n_search(self):
        tr = float(self.slider_tr.get())
        self.log(f"🔍 Bắt đầu đối chiếu 1:N cho ảnh: {os.path.basename(self.current_query_path)} (Ngưỡng: {tr:.3f})...")

        res, err = self.gallery.identify_face_1_to_n(self.current_query_path, top_k=10, threshold=tr)
        if err:
            messagebox.showerror("Lỗi Quét", err)
            self.log(f"❌ Lỗi: {err}")
            return

        self.scan_results = res
        best = res["best_match"]

        # Cập nhật banner verdict
        if res["identified"] and best:
            v_text = f"🟢 ĐÃ XÁC ĐỊNH DANH TÍNH: {best['face_id']} | Tệp: {best['filename']} (Cosine: {best['cosine_sim']:.4f} >= {tr:.3f})"
            self.lbl_verdict.config(text=v_text, fg=self.COLOR_GREEN, bg="#052211")
            self.verdict_frame.config(bg="#052211")
        else:
            v_text = f"🔴 CHƯA XÁC ĐỊNH ĐƯỢC DANH TÍNH TRONG KHO (Không có mẫu nào đạt ngưỡng {tr:.3f})"
            self.lbl_verdict.config(text=v_text, fg=self.COLOR_RED, bg="#2b0808")
            self.verdict_frame.config(bg="#2b0808")

        # Cập nhật danh sách Treeview Top Match
        for item in self.tree.get_children():
            self.tree.delete(item)

        for idx, m in enumerate(res["top_k_matches"], 1):
            st = "MATCH ✓" if m["is_same_identity"] else "KHÁC"
            self.tree.insert("", "end", values=(idx, m["face_id"], m["folder"], m["filename"], f"{m['cosine_sim']:.4f}", st))

        # Tự động chọn dòng đầu tiên nếu có
        children = self.tree.get_children()
        if children:
            self.tree.selection_set(children[0])

        self.log(f"✓ Đã quét xong! Đã đối chiếu với {res['total_gallery_faces']} mẫu trong kho CSDL.")
        self.render_query_canvas(best)

    def render_query_canvas(self, matched_item=None):
        if not hasattr(self, 'current_query_path') or not self.current_query_path:
            return

        img_q = cv2.imread(self.current_query_path)
        if img_q is None:
            return

        cw = self.canvas_query.winfo_width()
        ch = self.canvas_query.winfo_height()
        if cw <= 20 or ch <= 20:
            cw, ch = 650, 420

        self.canvas_query.delete("all")

        # Kích thước mỗi khung ảnh
        box_w = min(int(cw * 0.45), 320)
        box_h = min(int(ch * 0.85), 340)

        # 1. Vẽ Ảnh Query bên trái
        pos_q_x = int(cw * 0.03)
        pos_q_y = (ch - box_h) // 2
        hq, wq, _ = img_q.shape
        scale_q = min(box_w / wq, box_h / hq)
        nw_q, nh_q = int(wq * scale_q), int(hq * scale_q)
        resized_q = cv2.resize(img_q, (nw_q, nh_q))
        rgb_q = cv2.cvtColor(resized_q, cv2.COLOR_BGR2RGB)
        pil_q = Image.fromarray(rgb_q)

        self.photo_query = ImageTk.PhotoImage(pil_q)
        self.canvas_query.create_image(pos_q_x + (box_w - nw_q)//2, pos_q_y + (box_h - nh_q)//2, anchor="nw", image=self.photo_query)
        self.canvas_query.create_rectangle(pos_q_x, pos_q_y, pos_q_x + box_w, pos_q_y + box_h, outline=self.COLOR_CYAN, width=2)
        self.canvas_query.create_text(pos_q_x + 10, pos_q_y + 18, text="[ ẢNH CẦN TÌM ]", fill=self.COLOR_CYAN, font=self.FONT_MONO_BOLD, anchor="w")

        # 2. Vẽ Ảnh ĐC (Matched Dataset Face) bên phải
        pos_m_x = cw - int(cw * 0.03) - box_w
        pos_m_y = (ch - box_h) // 2

        if matched_item and os.path.exists(matched_item["path"]):
            img_m = cv2.imread(matched_item["path"])
            if img_m is not None:
                hm, wm, _ = img_m.shape
                scale_m = min(box_w / wm, box_h / hm)
                nw_m, nh_m = int(wm * scale_m), int(hm * scale_m)
                resized_m = cv2.resize(img_m, (nw_m, nh_m))
                rgb_m = cv2.cvtColor(resized_m, cv2.COLOR_BGR2RGB)
                pil_m = Image.fromarray(rgb_m)

                self.photo_match = ImageTk.PhotoImage(pil_m)
                self.canvas_query.create_image(pos_m_x + (box_w - nw_m)//2, pos_m_y + (box_h - nh_m)//2, anchor="nw", image=self.photo_match)
                
                m_color = self.COLOR_GREEN if matched_item["is_same_identity"] else self.COLOR_RED
                self.canvas_query.create_rectangle(pos_m_x, pos_m_y, pos_m_x + box_w, pos_m_y + box_h, outline=m_color, width=2)
                self.canvas_query.create_text(pos_m_x + 10, pos_m_y + 18, text=f"[ ĐỐI CHIẾU: {matched_item['face_id']} ]", fill=m_color, font=self.FONT_MONO_BOLD, anchor="w")

                # Đường nối vector & Nhãn ở giữa
                center_x = (pos_q_x + box_w + pos_m_x) // 2
                center_y = pos_q_y + box_h // 2
                self.canvas_query.create_line(pos_q_x + box_w, center_y, pos_m_x, center_y, fill=m_color, width=2, dash=(4, 4))
                
                lbl_text = f"COSINE: {matched_item['cosine_sim']:.4f}\n({matched_item['match_percentage']}%)"
                self.canvas_query.create_text(center_x, center_y - 20, text=lbl_text, fill=m_color, font=self.FONT_MONO_BOLD, anchor="center")
        else:
            self.canvas_query.create_rectangle(pos_m_x, pos_m_y, pos_m_x + box_w, pos_m_y + box_h, outline=self.COLOR_TEXT_DIM, width=1)
            self.canvas_query.create_text(pos_m_x + box_w//2, pos_m_y + box_h//2, text="[ CHỜ CHỌN MẪU ĐỐI CHIẾU ]", fill=self.COLOR_TEXT_DIM, font=self.FONT_MONO, anchor="center")


def main():
    parser = argparse.ArgumentParser(description="Script Quét Nhận dạng FaceID 1:N trong kho CSDL Dataset")
    parser.add_argument("--query", type=str, help="Đường dẫn ảnh cần quét nhận dạng danh tính")
    parser.add_argument("--sync", action="store_true", help="Tự động quét và cập nhật chỉ mục kho FaceID")
    parser.add_argument("--rebuild", action="store_true", help="Làm mới và quét lại từ đầu toàn bộ kho CSDL FaceID")
    parser.add_argument("--gui", action="store_true", help="Bật Giao diện Cyberpunk HUD 1:N Scanner")

    args = parser.parse_args()

    gallery = MultiFaceIDGallery()

    if args.rebuild or args.sync:
        gallery.build_or_update_gallery(force_rebuild=args.rebuild)

    if args.gui or len(sys.argv) == 1:
        root = tk.Tk()
        app = CyberpunkFaceIDScannerGUI(root)
        root.mainloop()
    elif args.query:
        res, err = gallery.identify_face_1_to_n(args.query)
        if err:
            print(f"❌ Lỗi: {err}")
        else:
            print(json.dumps(res, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
