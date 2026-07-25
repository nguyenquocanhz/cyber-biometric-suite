import cv2
import numpy as np
import os
import time
import urllib.request
import mediapipe as mp
from mediapipe.tasks import python
from mediapipe.tasks.python import vision


def draw_hand(frame, hand_lms, width, height):
    # Định nghĩa các cặp nối để tạo thành xương bàn tay
    connections = [
        # Ngón cái
        (0, 1), (1, 2), (2, 3), (3, 4),
        # Ngón trỏ
        (0, 5), (5, 6), (6, 7), (7, 8),
        # Ngón giữa
        (0, 9), (9, 10), (10, 11), (11, 12),
        # Ngón áp út
        (0, 13), (13, 14), (14, 15), (15, 16),
        # Ngón út
        (0, 17), (17, 18), (18, 19), (19, 20),
        # Nối lòng bàn tay
        (5, 9), (9, 13), (13, 17)
    ]
    
    # Vẽ các đường nối xương màu xám
    for start, end in connections:
        pt1 = (int(hand_lms[start].x * width), int(hand_lms[start].y * height))
        pt2 = (int(hand_lms[end].x * width), int(hand_lms[end].y * height))
        cv2.line(frame, pt1, pt2, (180, 180, 180), 2)
        
    # Vẽ các khớp ngón tay tròn nhỏ màu vàng lá mạ
    for lm in hand_lms:
        pt = (int(lm.x * width), int(lm.y * height))
        cv2.circle(frame, pt, 5, (0, 255, 128), -1)

def beautify_frame(frame):
    # 1. Làm mịn da thông minh (Bilateral Filter) giữ sắc nét các góc cạnh mắt, môi
    smoothed = cv2.bilateralFilter(frame, d=9, sigmaColor=65, sigmaSpace=65)
    
    # 2. Tăng độ sáng và tạo màu trắng hồng bằng LAB Color Space
    lab = cv2.cvtColor(smoothed, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(lab)
    l = cv2.add(l, 8)  # Nâng nhẹ độ sáng da
    a = cv2.add(a, 4)  # Thêm chút sắc hồng ấm áp cho da khỏe mạnh
    beautified = cv2.merge((l, a, b))
    beautified = cv2.cvtColor(beautified, cv2.COLOR_LAB2BGR)
    
    # 3. Làm sắc nét các chi tiết nhỏ như mắt, sợi tóc (Unsharp Masking)
    gaussian = cv2.GaussianBlur(frame, (9, 9), 10.0)
    details = cv2.subtract(frame, gaussian)
    sharpened = cv2.addWeighted(beautified, 1.0, details, 0.25, 0)
    
    # 4. Trộn với ảnh gốc để giữ độ tự nhiên
    output = cv2.addWeighted(sharpened, 0.8, frame, 0.2, 0)
    return output

def main():
    # 1. Tự động tải xuống model Google Hand Landmarker nếu chưa có
    model_url = "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task"
    model_path = os.path.join(os.path.dirname(__file__), "hand_landmarker.task")
    
    if not os.path.exists(model_path):
        print("Đang tải tệp tin model AI hand_landmarker.task từ Google...")
        urllib.request.urlretrieve(model_url, model_path)
        print("Tải thành công!")

    # 2. Khởi tạo detector bàn tay thế hệ mới (Tasks API)
    base_options = python.BaseOptions(model_asset_path=model_path)
    options = vision.HandLandmarkerOptions(
        base_options=base_options,
        running_mode=vision.RunningMode.IMAGE,
        num_hands=1
    )
    detector = vision.HandLandmarker.create_from_options(options)

    # 3. Khởi tạo Webcam
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("Lỗi: Không thể mở Webcam. Vui lòng kiểm tra lại thiết bị!")
        return

    # Tối ưu hóa độ phân giải camera về 640x480 để tăng tốc độ xử lý AI (Tăng FPS)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)

    # Lấy thông số camera sau khi thiết lập
    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    
    # 4. Tạo bảng vẽ phụ (Canvas) bằng màu đen
    canvas = np.zeros((height, width, 3), dtype=np.uint8)

    # Định nghĩa các màu vẽ (BGR format cho OpenCV)
    colors = [
        (0, 0, 255),    # Đỏ (Red)
        (0, 255, 0),    # Xanh lá (Green)
        (255, 0, 0),    # Xanh dương (Blue)
        (0, 255, 255),  # Vàng (Yellow)
        (0, 0, 0)       # Tẩy / Trùng màu nền (Eraser)
    ]
    color_index = 0  # Chọn màu Đỏ ban đầu
    draw_color = colors[color_index]
    brush_thickness = 8

    # Tọa độ điểm vẽ trước đó (để vẽ đường thẳng liên tục)
    prev_x, prev_y = 0, 0

    # Kích thước và thông tin thanh công cụ (Toolbar)
    toolbar_height = 80
    num_buttons = 5
    button_width = width // num_buttons
    button_labels = ["RED", "GREEN", "BLUE", "YELLOW", "CLEAR"]

    print("HƯỚNG DẪN SỬ DỤNG:")
    print("-----------------------------------------------------------------")
    print("👉 Giơ 1 ngón trỏ: Chế độ VẼ lên không trung.")
    print("👉 Giơ 2 ngón (Trỏ + Giữa): Chế độ DI CHUYỂN không vẽ / CHỌN MÀU ở menu trên đầu.")
    print("⌨️ Nhấn phím 'q' trên bàn phím để ĐÓNG phần mềm.")
    print("-----------------------------------------------------------------")

    prev_time = 0
    fist_frames = 0
    capture_frames = 0
    flash_counter = 0

    while cap.isOpened():
        success, frame = cap.read()
        if not success:
            break

        # Đảo ảnh chiều ngang để vẽ thuận tay (giống soi gương)
        frame = cv2.flip(frame, 1)

        # Áp dụng bộ lọc làm đẹp và tăng nét cho khuôn mặt (Douyin Beauty Filter)
        frame = beautify_frame(frame)

        # Chuyển đổi BGR của OpenCV thành đối tượng Image của MediaPipe
        rgb_data = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_data)
        
        # Nhận diện bàn tay
        result = detector.detect(mp_image)

        # Trạng thái các ngón tay mặc định
        index_up = False
        middle_up = False
        x, y = 0, 0
        take_snapshot_now = False
        hand_box = None

        # Nếu phát hiện thấy bàn tay
        if result.hand_landmarks:
            hand_lms = result.hand_landmarks[0]
            
            x = int(hand_lms[8].x * width)
            y = int(hand_lms[8].y * height)
            
            # Kiểm tra trạng thái dựng đứng của 4 ngón chính
            if hand_lms[8].y < hand_lms[6].y:
                index_up = True
            if hand_lms[12].y < hand_lms[10].y:
                middle_up = True
                
            ring_up = hand_lms[16].y < hand_lms[14].y
            pinky_up = hand_lms[20].y < hand_lms[18].y

            # Phát hiện ngón cái chỉ thiên (Thumb Up)
            thumb_up = hand_lms[4].y < hand_lms[3].y and hand_lms[3].y < hand_lms[2].y
            
            is_fist = (not thumb_up) and (not index_up) and (not middle_up) and (not ring_up) and (not pinky_up)
            is_like = thumb_up and (not index_up) and (not middle_up) and (not ring_up) and (not pinky_up)

            # Tính toán khung chứa bàn tay (Bounding Box) để chụp ảnh
            x_coords = [int(lm.x * width) for lm in hand_lms]
            y_coords = [int(lm.y * height) for lm in hand_lms]
            pad = 30
            x1 = max(0, min(x_coords) - pad)
            y1 = max(0, min(y_coords) - pad)
            x2 = min(width, max(x_coords) + pad)
            y2 = min(height, max(y_coords) + pad)
            hand_box = (x1, y1, x2, y2)

            # CỬ CHỈ 1: Nắm tay lại -> Giữ 1 giây để XÓA CANVAS
            if is_fist:
                if fist_frames <= 30:
                    fist_frames += 1
                    
                    # Vẽ thanh tiến trình loading ở giữa-dưới màn hình
                    bar_w, bar_h = 240, 16
                    start_x = (width - bar_w) // 2
                    start_y = height - 60
                    
                    cv2.rectangle(frame, (start_x, start_y), (start_x + bar_w, start_y + bar_h), (50, 50, 50), -1)
                    fill_w = int(bar_w * (min(fist_frames, 30) / 30.0))
                    cv2.rectangle(frame, (start_x, start_y), (start_x + fill_w, start_y + bar_h), (0, 0, 255), -1)
                    
                    cv2.putText(frame, "HOLD TO CLEAR", (start_x + 50, start_y - 8),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 4)
                    cv2.putText(frame, "HOLD TO CLEAR", (start_x + 50, start_y - 8),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 2)
                    
                    if fist_frames == 30:
                        canvas = np.zeros((height, width, 3), dtype=np.uint8)
                        fist_frames = 31
            else:
                fist_frames = 0

            # CỬ CHỈ 2: Like (Ngón cái chỉ thiên, 4 ngón còn lại gập) -> Giữ 1 giây để CHỤP ẢNH
            if is_like:
                # Vẽ 4 góc khung lấy nét camera (Autofocus brackets) màu xanh lá cực nét
                cl = 20
                cv2.line(frame, (x1, y1), (x1 + cl, y1), (0, 255, 0), 2)
                cv2.line(frame, (x1, y1), (x1, y1 + cl), (0, 255, 0), 2)
                cv2.line(frame, (x2, y1), (x2 - cl, y1), (0, 255, 0), 2)
                cv2.line(frame, (x2, y1), (x2, y1 + cl), (0, 255, 0), 2)
                cv2.line(frame, (x1, y2), (x1 + cl, y2), (0, 255, 0), 2)
                cv2.line(frame, (x1, y2), (x1, y2 - cl), (0, 255, 0), 2)
                cv2.line(frame, (x2, y2), (x2 - cl, y2), (0, 255, 0), 2)
                cv2.line(frame, (x2, y2), (x2, y2 - cl), (0, 255, 0), 2)

                if capture_frames <= 30:
                    capture_frames += 1
                    
                    # Vẽ thanh tiến trình màu xanh dương ở giữa-dưới màn hình
                    bar_w, bar_h = 240, 16
                    start_x = (width - bar_w) // 2
                    start_y = height - 60
                    
                    cv2.rectangle(frame, (start_x, start_y), (start_x + bar_w, start_y + bar_h), (50, 50, 50), -1)
                    fill_w = int(bar_w * (min(capture_frames, 30) / 30.0))
                    cv2.rectangle(frame, (start_x, start_y), (start_x + fill_w, start_y + bar_h), (255, 128, 0), -1)
                    
                    cv2.putText(frame, "TAKING PHOTO", (start_x + 55, start_y - 8),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 4)
                    cv2.putText(frame, "TAKING PHOTO", (start_x + 55, start_y - 8),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 128, 0), 2)
                    
                    if capture_frames == 30:
                        take_snapshot_now = True
                        capture_frames = 31  # Khóa trạng thái cho đến khi mở tay ra
            else:
                if capture_frames != 31:
                    capture_frames = 0
                else:
                    # Chờ xòe/mở tay ra mới reset lock
                    capture_frames = 0

            # Hiển thị thông báo "PHOTO SAVED!" góc dưới
            if capture_frames > 30:
                cv2.putText(frame, "PHOTO SAVED!", (width // 2 - 80, height - 90),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 0), 4)
                cv2.putText(frame, "PHOTO SAVED!", (width // 2 - 80, height - 90),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)

            # Vẽ bộ khung xương tay tùy biến lên màn hình
            draw_hand(frame, hand_lms, width, height)
        else:
            fist_frames = 0
            if capture_frames != 31:
                capture_frames = 0

        # 5. Xử lý cử chỉ vẽ hoặc chọn công cụ
        if index_up:
            # CHẾ ĐỘ 1: Chọn màu / Di chuyển (Giơ cả ngón trỏ và ngón giữa)
            if middle_up:
                prev_x, prev_y = 0, 0  # Reset điểm vẽ trước đó để tránh nối nét vẽ đột ngột
                
                # Vẽ vòng tròn chỉ thị di chuyển (Màu xám)
                cv2.circle(frame, (x, y), 15, (200, 200, 200), cv2.FILLED)
                
                # Nếu di chuyển lên thanh công cụ
                if y < toolbar_height:
                    selected_col = x // button_width
                    if selected_col < 4:
                        color_index = selected_col
                        draw_color = colors[color_index]
                    elif selected_col == 4:
                        # Bấm nút CLEAR -> Xóa toàn bộ bảng vẽ
                        canvas = np.zeros((height, width, 3), dtype=np.uint8)
            
            # CHẾ ĐỘ 2: Vẽ hình (Chỉ giơ ngón trỏ)
            else:
                # Vẽ vòng tròn chỉ thị đang vẽ (màu đang chọn)
                cv2.circle(frame, (x, y), brush_thickness, draw_color, cv2.FILLED)
                
                if prev_x == 0 and prev_y == 0:
                    prev_x, prev_y = x, y
                
                # Vẽ đường thẳng nối từ điểm cũ sang điểm mới trên Canvas
                if y > toolbar_height:  # Không vẽ đè lên thanh công cụ
                    cv2.line(canvas, (prev_x, prev_y), (x, y), draw_color, brush_thickness)
                
                prev_x, prev_y = x, y
        else:
            # Không giơ tay hoặc nắm tay -> Dừng vẽ
            prev_x, prev_y = 0, 0

        # 6. Vẽ Giao diện Thanh công cụ (Toolbar) đè lên màn hình
        # Vẽ khung nền menu đen mờ
        cv2.rectangle(frame, (0, 0), (width, toolbar_height), (30, 30, 30), cv2.FILLED)
        
        # Vẽ các ô màu
        for i in range(num_buttons):
            x1 = i * button_width
            x2 = (i + 1) * button_width
            
            if i < 4:
                # Vẽ ô màu tương ứng
                cv2.rectangle(frame, (x1 + 5, 5), (x2 - 5, toolbar_height - 5), colors[i], cv2.FILLED)
                # Đánh dấu ô màu đang chọn
                if i == color_index:
                    cv2.rectangle(frame, (x1 + 2, 2), (x2 - 2, toolbar_height - 2), (255, 255, 255), 3)
            else:
                # Vẽ nút CLEAR
                cv2.rectangle(frame, (x1 + 5, 5), (x2 - 5, toolbar_height - 5), (100, 100, 100), cv2.FILLED)
            
            # Viết chữ nhãn (Căn giữa tuyệt đối và có viền đen sắc nét)
            text_size = cv2.getTextSize(button_labels[i], cv2.FONT_HERSHEY_SIMPLEX, 0.6, 2)[0]
            text_x = x1 + (button_width - text_size[0]) // 2
            text_y = toolbar_height // 2 + text_size[1] // 2
            
            # Vẽ viền đen trước
            cv2.putText(frame, button_labels[i], (text_x, text_y),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 4)
            # Vẽ chữ trắng chính đè lên
            cv2.putText(frame, button_labels[i], (text_x, text_y),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

        # 7. Trộn bảng vẽ Canvas màu sắc lên luồng Camera thực tế (Hòa trộn khử răng cưa mượt mà)
        img_gray = cv2.cvtColor(canvas, cv2.COLOR_BGR2GRAY)
        
        # Làm mịn nhẹ mặt nạ xám để nét vẽ biên được mượt mà hơn
        img_mask = cv2.GaussianBlur(img_gray, (3, 3), 0)
        
        # Tạo tỷ lệ alpha (0.0 đến 1.0)
        alpha = img_mask.astype(float) / 255.0
        alpha = np.expand_dims(alpha, axis=2)
        
        # Hòa trộn: output = canvas * alpha + frame * (1.0 - alpha)
        output_frame = cv2.convertScaleAbs(canvas * alpha + frame * (1.0 - alpha))

        # Tính toán và hiển thị FPS thực tế lên góc màn hình
        curr_time = time.time()
        fps = 1 / (curr_time - prev_time) if (curr_time - prev_time) > 0 else 0
        prev_time = curr_time
        
        # Vẽ FPS có viền đen sắc nét chống lóa nền camera
        cv2.putText(
            output_frame, f"FPS: {int(fps)}", (10, height - 15),
            cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 4
        )
        cv2.putText(
            output_frame, f"FPS: {int(fps)}", (10, height - 15),
            cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2
        )

        # 8. Thực hiện lưu ảnh chụp khi được kích hoạt
        if take_snapshot_now and hand_box is not None:
            timestamp = time.strftime("%Y%m%d_%H%M%S")
            filename_full = f"snapshot_{timestamp}.png"
            filename_crop = f"hand_crop_{timestamp}.png"
            
            # Lưu ảnh toàn bộ màn hình (gồm nét vẽ và camera làm đẹp)
            cv2.imwrite(filename_full, output_frame)
            
            # Lưu ảnh cắt vùng bàn tay từ frame camera làm đẹp
            x1, y1, x2, y2 = hand_box
            hand_crop = frame[y1:y2, x1:x2]
            if hand_crop.size > 0:
                cv2.imwrite(filename_crop, hand_crop)
                
            print(f"📸 Đã chụp và lưu ảnh: {filename_full} & {filename_crop}")
            flash_counter = 3  # Nháy sáng trắng màn hình trong 3 frames
            
        # 9. Hiệu ứng chớp sáng trắng màn hình (Camera Flash)
        if flash_counter > 0:
            flash_counter -= 1
            output_frame = np.full_like(output_frame, 255)

        # Hiển thị kết quả ra màn hình
        cv2.imshow("Air Drawing App - Antigravity AI", output_frame)

        # Bấm nút 'q' trên bàn phím để tắt phần mềm
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    # Giải phóng camera và bộ nhớ
    cap.release()
    detector.close()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    main()
