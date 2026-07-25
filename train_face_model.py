import os
import sys
import cv2
import numpy as np
import json
import math
import time
import shutil
from datetime import datetime

# Tắt cảnh báo C++ không cần thiết từ OpenCV DNN
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


class FaceDataAugmenter:
    """
    Mô-đun Tự động Làm giàu Dữ liệu Gương mặt (Data Augmentation Engine).
    Biến đổi ảnh gốc bằng các kỹ thuật: Lật ngang, Xoay nhẹ, Đổi độ sáng/tương phản, Mờ/Sắc nét, Color Jitter.
    """
    def __init__(self, target_size=(112, 112)):
        self.target_size = target_size

    def augment_face_crop(self, face_crop):
        """Tạo ra 6 phiên bản dữ liệu được làm giàu từ 1 crop ảnh gốc."""
        if face_crop is None or face_crop.size == 0:
            return []

        augmented_images = []
        h, w, _ = face_crop.shape

        # 1. Ảnh gốc resize chuẩn
        img_orig = cv2.resize(face_crop, self.target_size, interpolation=cv2.INTER_CUBIC)
        augmented_images.append(img_orig)

        # 2. Lật ngang (Horizontal Flip)
        img_flip = cv2.flip(img_orig, 1)
        augmented_images.append(img_flip)

        # 3. Tăng độ sáng (Brightness +20%)
        img_bright = cv2.convertScaleAbs(img_orig, alpha=1.15, beta=20)
        augmented_images.append(img_bright)

        # 4. Giảm độ sáng & Tăng độ tương phản
        img_contrast = cv2.convertScaleAbs(img_orig, alpha=1.25, beta=-15)
        augmented_images.append(img_contrast)

        # 5. Xoay nghiêng góc +10 độ
        matrix_p10 = cv2.getRotationMatrix2D((self.target_size[0] // 2, self.target_size[1] // 2), 10, 1.0)
        img_rot1 = cv2.warpAffine(img_orig, matrix_p10, self.target_size, borderMode=cv2.BORDER_REPLICATE)
        augmented_images.append(img_rot1)

        # 6. Xoay nghiêng góc -10 độ
        matrix_m10 = cv2.getRotationMatrix2D((self.target_size[0] // 2, self.target_size[1] // 2), -10, 1.0)
        img_rot2 = cv2.warpAffine(img_orig, matrix_m10, self.target_size, borderMode=cv2.BORDER_REPLICATE)
        augmented_images.append(img_rot2)

        # 7. Sắc nét ảnh (Sharpen Filter)
        kernel_sharpen = np.array([[0, -1, 0], [-1, 5, -1], [0, -1, 0]])
        img_sharp = cv2.filter2D(img_orig, -1, kernel_sharpen)
        augmented_images.append(img_sharp)

        return augmented_images


class FaceModelTrainer:
    """
    Hệ thống Tự động Làm giàu Dữ liệu & Trích xuất Feature 128-D SFace,
    Huấn luyện Mô hình Machine Learning Softmax/Cosine Classifier mới.
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

        self.augmenter = FaceDataAugmenter()

    def extract_features_from_crop(self, crop_bgr):
        """Trích xuất 128-D SFace feature vector từ crop ảnh đã làm giàu."""
        if self.recognizer is None or crop_bgr is None:
            return None

        h, w, _ = crop_bgr.shape
        # Định dạng fake face box cho SFace align (x, y, w, h, l0_x, l0_y...)
        fake_face = np.array([0, 0, w, h, int(w*0.3), int(h*0.3), int(w*0.7), int(h*0.3), int(w*0.5), int(h*0.5), int(w*0.35), int(h*0.7), int(w*0.65), int(h*0.7), 1.0], dtype=np.float32)
        try:
            aligned = self.recognizer.alignCrop(crop_bgr, fake_face)
            feat = self.recognizer.feature(aligned)
            norm_feat = cv2.normalize(feat, None).flatten()
            return norm_feat
        except Exception:
            return None

    def scan_and_enrich_dataset(self, dataset_dirs):
        """
        Quét qua các thư mục dữ liệu, phát hiện mặt, làm giàu dữ liệu và trích xuất vector 128-D.
        dataset_dirs: dict {"only_nam": 0, "only_nu": 1, ...} hoặc list các folder
        """
        X_features = []
        y_labels = []
        label_names = []
        processed_stats = {}

        print("🚀 Bắt đầu tiến trình TỰ ĐỘNG LÀM GIÀU DỮ LIỆU & TRÍCH XUẤT ĐẶC TRƯNG...")

        for label_idx, (class_name, dir_path) in enumerate(dataset_dirs.items()):
            if not os.path.exists(dir_path):
                print(f"⚠️ Thư mục '{dir_path}' không tồn tại, bỏ qua.")
                continue

            label_names.append(class_name)
            valid_exts = {".jpg", ".jpeg", ".png", ".webp"}
            files = [f for f in os.listdir(dir_path) if os.path.splitext(f.lower())[1] in valid_exts]

            print(f"📁 Đang xử lý lớp '{class_name}' ({len(files)} ảnh gốc)...")

            original_faces = 0
            augmented_faces = 0

            for fname in files:
                fpath = os.path.join(dir_path, fname)
                img = cv2.imread(fpath)
                if img is None:
                    continue

                h, w, _ = img.shape
                self.detector.setInputSize((w, h))
                _, faces = self.detector.detect(img)

                if faces is None or len(faces) == 0:
                    continue

                for face in faces:
                    original_faces += 1
                    # Cắt aligned crop từ ảnh gốc
                    try:
                        aligned_crop = self.recognizer.alignCrop(img, face)
                    except Exception:
                        box = face[0:4].astype(int)
                        x, y, bw, bh = box
                        x, y = max(0, x), max(0, y)
                        aligned_crop = img[y:y+bh, x:x+bw]

                    if aligned_crop is None or aligned_crop.size == 0:
                        continue

                    # Làm giàu dữ liệu 7x cho mỗi khuôn mặt
                    aug_crops = self.augmenter.augment_face_crop(aligned_crop)

                    for aug_img in aug_crops:
                        feat = self.extract_features_from_crop(aug_img)
                        if feat is not None and len(feat) == 128:
                            X_features.append(feat)
                            y_labels.append(label_idx)
                            augmented_faces += 1

            processed_stats[class_name] = {
                "original_faces": original_faces,
                "enriched_samples": augmented_faces
            }
            print(f"   ✓ Lớp '{class_name}': {original_faces} mặt gốc ➔ Làm giàu thành {augmented_faces} mẫu 128-D!")

        return np.array(X_features, dtype=np.float32), np.array(y_labels, dtype=np.int32), label_names, processed_stats

    def train_custom_classifier(self, X, y, label_names, epochs=300, lr=0.05):
        """
        Huấn luyện Mô hình Phân loại Softmax Multi-Class & Cosine Centroid Classifier trên NumPy.
        """
        num_samples, num_features = X.shape
        num_classes = len(label_names)

        print(f"\n🧠 BẮT ĐẦU HUẤN LUYỆN MÔ HÌNH MỚI (Số mẫu làm giàu: {num_samples}, Số lớp: {num_classes})...")

        # 1. Tính toán Cosine Centroid Vectors cho mỗi lớp (Prototype Embeddings)
        centroids = np.zeros((num_classes, num_features), dtype=np.float32)
        for c in range(num_classes):
            class_mask = (y == c)
            if np.sum(class_mask) > 0:
                mean_vec = np.mean(X[class_mask], axis=0)
                centroids[c] = mean_vec / (np.linalg.norm(mean_vec) + 1e-8)

        # 2. Train Softmax Linear Classifier (Weights & Biases)
        # Weights shape: (num_classes, 128)
        W = np.random.randn(num_classes, num_features).astype(np.float32) * 0.01
        b = np.zeros((num_classes,), dtype=np.float32)

        # Chuyển y sang One-hot Matrix
        Y_onehot = np.zeros((num_samples, num_classes), dtype=np.float32)
        Y_onehot[np.arange(num_samples), y] = 1.0

        best_acc = 0.0
        best_W, best_b = W.copy(), b.copy()

        for epoch in range(1, epochs + 1):
            # Forward pass: logits = X @ W.T + b
            logits = np.dot(X, W.T) + b
            # Softmax
            exp_logits = np.exp(logits - np.max(logits, axis=1, keepdims=True))
            probs = exp_logits / np.sum(exp_logits, axis=1, keepdims=True)

            # Loss: Cross-Entropy
            loss = -np.mean(np.sum(Y_onehot * np.log(probs + 1e-8), axis=1))

            # Backward pass (Gradients)
            dz = (probs - Y_onehot) / num_samples
            dW = np.dot(dz.T, X)
            db = np.sum(dz, axis=0)

            # Update weights
            W -= lr * dW
            b -= lr * db

            # Compute Accuracy
            preds = np.argmax(probs, axis=1)
            acc = np.mean(preds == y) * 100.0

            if acc > best_acc:
                best_acc = acc
                best_W, best_b = W.copy(), b.copy()

            if epoch % 50 == 0 or epoch == epochs:
                print(f"   • Epoch [{epoch:03d}/{epochs}] ➔ CrossEntropy Loss: {loss:.4f} | Accuracy: {acc:.2f}%")

        print(f"✨ HOÀN TẤT TRAIN! Độ chính xác cao nhất trên tập dữ liệu làm giàu: {best_acc:.2f}%\n")

        model_data = {
            "metadata": {
                "created_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                "num_samples": int(num_samples),
                "num_classes": int(num_classes),
                "accuracy": float(round(best_acc, 2)),
                "label_names": label_names
            },
            "centroids": centroids.tolist(),
            "weights": best_W.tolist(),
            "biases": best_b.tolist()
        }

        return model_data

    def save_model(self, model_data, output_path="custom_face_model.json"):
        script_dir = os.path.dirname(os.path.abspath(__file__))
        save_file = os.path.join(script_dir, output_path)
        with open(save_file, "w", encoding="utf-8") as f:
            json.dump(model_data, f, indent=2, ensure_ascii=False)
        print(f"💾 Đã lưu mô hình mới tại: {save_file}")
        return save_file


def run_auto_enrichment_and_train():
    """Hàm chạy tự động quét toàn bộ thư mục dữ liệu và train mô hình mới."""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Định nghĩa các thư mục nhãn dữ liệu có sẵn
    dataset_dirs = {
        "only_nam": os.path.join(script_dir, "only_nam"),
        "only_nu": os.path.join(script_dir, "only_nu"),
        "nam_va_nu": os.path.join(script_dir, "nam_va_nu")
    }

    trainer = FaceModelTrainer()
    X, y, label_names, stats = trainer.scan_and_enrich_dataset(dataset_dirs)

    if len(X) == 0:
        print("❌ Không tìm thấy dữ liệu khuôn mặt hợp lệ trong các thư mục để làm giàu và train.")
        return None

    model_data = trainer.train_custom_classifier(X, y, label_names)
    saved_file = trainer.save_model(model_data)

    return saved_file


if __name__ == "__main__":
    run_auto_enrichment_and_train()
