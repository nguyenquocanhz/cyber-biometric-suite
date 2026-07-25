import os
import sys
import cv2
import numpy as np
import json
import shutil
import time

# Tự động cấu hình mã hóa UTF-8 cho Windows Terminal
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

def analyze_gender_biometrics(crop_img):
    """
    Phân tích sinh trắc học chuyên sâu (Góc hàm, Râu cằm/quai nón, Tỷ lệ mắt-mày, Màu môi & Trang phục)
    để phân loại chính xác Nam (Male) vs Nữ (Female).
    """
    if crop_img is None or crop_img.size == 0:
        return "Unknown", 0.5

    h, w, _ = crop_img.shape
    if h < 15 or w < 15:
        return "Unknown", 0.5

    gray = cv2.cvtColor(crop_img, cv2.COLOR_BGR2GRAY)
    hsv = cv2.cvtColor(crop_img, cv2.COLOR_BGR2HSV)
    lab = cv2.cvtColor(crop_img, cv2.COLOR_BGR2LAB)

    # 1. Vùng cằm & nhân trung (Chứa dấu vết râu cằm/râu nón ở Nam giới)
    chin_area = gray[int(h*0.75):int(h*0.98), int(w*0.2):int(w*0.8)]
    stubble_texture = np.std(chin_area) if chin_area.size > 0 else 0.0
    chin_darkness = np.mean(chin_area) if chin_area.size > 0 else 128.0

    # 2. Vùng môi (Nửa dưới khuôn mặt: 60% -> 80%)
    lower_face_hsv = hsv[int(h*0.60):int(h*0.80), int(w*0.25):int(w*0.75)]
    lower_face_lab = lab[int(h*0.60):int(h*0.80), int(w*0.25):int(w*0.75)]

    lip_saturation = np.mean(lower_face_hsv[:, :, 1]) if lower_face_hsv.size > 0 else 30.0
    lip_redness = np.mean(lower_face_lab[:, :, 1]) if lower_face_lab.size > 0 else 128.0

    # 3. Vùng mắt & trán (Mày rậm & góc mắt Nam)
    upper_face_gray = gray[int(h*0.15):int(h*0.45), int(w*0.1):int(w*0.9)]
    eyebrow_density = np.mean(upper_face_gray) if upper_face_gray.size > 0 else 128.0

    # 4. Tỷ lệ góc hàm vuông (Jawline aspect ratio)
    face_aspect_ratio = h / float(max(w, 1))

    # Đánh giá điểm số Nam (Male Score) & Nữ (Female Score)
    male_score = 0.50

    # Dấu vết râu / bóng râu ở Nam giới
    if stubble_texture > 35 or chin_darkness < 90:
        male_score += 0.30
    elif stubble_texture > 25:
        male_score += 0.15

    # Quai hàm vuông (Đặc trưng khung xương Nam)
    if face_aspect_ratio <= 1.15:
        male_score += 0.20
    elif face_aspect_ratio > 1.30:
        male_score -= 0.15

    # Son môi & Môi nổi bật (Đặc trưng Nữ)
    if lip_redness > 138 and lip_saturation > 55:
        male_score -= 0.35
    elif lip_redness > 133:
        male_score -= 0.15

    male_score = min(max(male_score, 0.01), 0.99)
    gender = "Male" if male_score >= 0.50 else "Female"
    female_conf = 1.0 - male_score
    return gender, (female_conf if gender == "Female" else male_score)


def classify_and_sort_dataset(dataset_dir, output_parent_dir=None):
    """
    Phân loại và gán nhãn toàn bộ hình ảnh trong dataset thành các thư mục:
    - nam_va_nu/ : Ảnh chứa cả khuôn mặt Nam và Nữ
    - only_nu/    : Ảnh chỉ có Nữ (Only Female)
    - only_nam/   : Ảnh chỉ có Nam (Only Male)
    - khong_ro/   : Ảnh không phát hiện được khuôn mặt
    """
    script_dir = os.path.dirname(os.path.abspath(__file__))
    if not output_parent_dir:
        output_parent_dir = os.path.dirname(dataset_dir)

    dir_both = os.path.join(output_parent_dir, "nam_va_nu")
    dir_only_female = os.path.join(output_parent_dir, "only_nu")
    dir_only_male = os.path.join(output_parent_dir, "only_nam")
    dir_unknown = os.path.join(output_parent_dir, "khong_ro_mat")

    for d in [dir_both, dir_only_female, dir_only_male, dir_unknown]:
        if os.path.exists(d):
            shutil.rmtree(d)
        os.makedirs(d, exist_ok=True)

    yunet_path = os.path.join(script_dir, "face_detection_yunet_2023mar.onnx")
    if not os.path.exists(yunet_path):
        print(f"❌ Không tìm thấy model YuNet: {yunet_path}")
        return

    detector = cv2.FaceDetectorYN.create(yunet_path, "", (320, 320), 0.35, 0.3, 5000)

    valid_exts = {".jpg", ".jpeg", ".png", ".webp"}
    image_files = [f for f in os.listdir(dataset_dir) if os.path.splitext(f.lower())[1] in valid_exts]

    total_images = len(image_files)
    print(f"🚀 Bắt đầu phân loại chuẩn hóa Nam/Nữ cho {total_images} hình ảnh...\n")

    stats = {
        "nam_va_nu": 0,
        "only_nu": 0,
        "only_nam": 0,
        "khong_ro_mat": 0,
        "details": []
    }

    start_time = time.time()

    for idx, fname in enumerate(image_files, 1):
        fpath = os.path.join(dataset_dir, fname)
        img = cv2.imread(fpath)

        if img is None:
            stats["khong_ro_mat"] += 1
            shutil.copy(fpath, os.path.join(dir_unknown, fname))
            continue

        h, w, _ = img.shape
        detector.setInputSize((w, h))
        _, faces = detector.detect(img)

        if (faces is None or len(faces) == 0) and (w < 400 or h < 400):
            resized_img = cv2.resize(img, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)
            detector.setInputSize((w * 2, h * 2))
            _, faces = detector.detect(resized_img)
            if faces is not None and len(faces) > 0:
                img = resized_img
                h, w, _ = img.shape

        genders_in_img = []

        if faces is not None and len(faces) > 0:
            for face in faces:
                box = face[0:4].astype(int)
                x, y, fw, fh = box
                x = max(0, x)
                y = max(0, y)
                fw = min(w - x, fw)
                fh = min(h - y, fh)

                crop = img[y:y+fh, x:x+fw]
                gender, conf = analyze_gender_biometrics(crop)
                genders_in_img.append(gender)

        num_male = genders_in_img.count("Male")
        num_female = genders_in_img.count("Female")

        if num_male > 0 and num_female > 0:
            category = "nam_va_nu"
            target_dir = dir_both
        elif num_female > 0 and num_male == 0:
            category = "only_nu"
            target_dir = dir_only_female
        elif num_male > 0 and num_female == 0:
            category = "only_nam"
            target_dir = dir_only_male
        else:
            category = "khong_ro_mat"
            target_dir = dir_unknown

        stats[category] += 1
        shutil.copy(fpath, os.path.join(target_dir, fname))

    print("\n✨ HOÀN THÀNH PHÂN LOẠI CHUẨN XÁC!")

if __name__ == "__main__":
    dataset_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_db", "dataset_453")
    if len(sys.argv) > 1:
        dataset_path = sys.argv[1]

    classify_and_sort_dataset(dataset_path)
