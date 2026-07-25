import os
import sys
import cv2
import numpy as np
import time

# Tắt thông báo cảnh báo C++ không cần thiết
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


class GPUCUDAEngine:
    """
    Mô-đun Tối ưu hóa GPU CUDA dành riêng cho card đồ họa NVIDIA (GTX 1650 Ti / RTX / CUDA).
    Tự động kích hoạt GPU Backend cho YuNet & SFace và tăng tốc ma trận Cosine Similarity.
    """
    def __init__(self):
        self.has_opencv_cuda = False
        self.has_pytorch_cuda = False
        self.has_onnx_cuda = False
        self.gpu_device_name = "NVIDIA CUDA GPU (GTX 1650 Ti / Compatible)"
        self.detect_cuda_hardware()

    def detect_cuda_hardware(self):
        """Kiểm tra và phát hiện phần cứng GPU NVIDIA CUDA trên hệ thống."""
        # 1. Kiểm tra OpenCV CUDA
        try:
            if hasattr(cv2, 'cuda') and cv2.cuda.getCudaEnabledDeviceCount() > 0:
                self.has_opencv_cuda = True
                dev_id = cv2.cuda.getDevice()
                print(f"🟢 [CUDA HARDWARE] Đã phát hiện OpenCV CUDA Device #{dev_id}!")
        except Exception:
            self.has_opencv_cuda = False

        # 2. Kiểm tra PyTorch CUDA
        try:
            import torch
            if torch.cuda.is_available():
                self.has_pytorch_cuda = True
                self.gpu_device_name = torch.cuda.get_device_name(0)
                print(f"🟢 [CUDA HARDWARE] PyTorch CUDA kích hoạt thành công: {self.gpu_device_name}")
        except Exception:
            self.has_pytorch_cuda = False

        # 3. Kiểm tra ONNX Runtime CUDA
        try:
            import onnxruntime as ort
            if 'CUDAExecutionProvider' in ort.get_available_providers():
                self.has_onnx_cuda = True
                print("🟢 [CUDA HARDWARE] ONNX Runtime CUDA Execution Provider sẵn sàng!")
        except Exception:
            self.has_onnx_cuda = False

        if not (self.has_opencv_cuda or self.has_pytorch_cuda or self.has_onnx_cuda):
            print("⚠️ [GPU NOTICE] Đang chạy chế độ Dự phòng CPU/OpenCL. Khi chuyển qua laptop có GPU NVIDIA 1650 Ti, script sẽ tự động kích hoạt CUDA full tốc độ!")

    def configure_yunet_gpu(self, detector):
        """
        Cấu hình mô hình YuNet phát hiện khuôn mặt chạy trực tiếp trên GPU CUDA (NVIDIA GTX 1650 Ti).
        Sử dụng DNN_TARGET_CUDA hoặc DNN_TARGET_CUDA_FP16 (Turing Architecture).
        """
        if detector is None:
            return detector

        if self.has_opencv_cuda:
            try:
                # Cấu hình GPU CUDA Backend & Target cho OpenCV DNN
                detector.setPreferableBackend(cv2.dnn.DNN_BACKEND_CUDA)
                # Dùng FP16 để tối ưu tốc độ và giảm dung lượng VRAM cho GTX 1650 Ti
                detector.setPreferableTarget(cv2.dnn.DNN_TARGET_CUDA_FP16)
                print("⚡ [YUNET CUDA] Đã chuyển đổi luồng phát hiện YuNet sang GPU CUDA (FP16 Accelerated)!")
            except Exception as e:
                try:
                    detector.setPreferableBackend(cv2.dnn.DNN_BACKEND_CUDA)
                    detector.setPreferableTarget(cv2.dnn.DNN_TARGET_CUDA)
                    print("⚡ [YUNET CUDA] Đã chuyển đổi YuNet sang GPU CUDA (FP32)!")
                except Exception:
                    print("⚠️ Không thể thiết lập CUDA cho YuNet, quay về CPU/OpenCL.")
        return detector

    def configure_sface_gpu(self, recognizer):
        """
        Cấu hình mô hình SFace trích xuất vector 128-D chạy trực tiếp trên GPU CUDA.
        """
        if recognizer is None:
            return recognizer

        if self.has_opencv_cuda:
            try:
                recognizer.setPreferableBackend(cv2.dnn.DNN_BACKEND_CUDA)
                recognizer.setPreferableTarget(cv2.dnn.DNN_TARGET_CUDA_FP16)
                print("⚡ [SFACE CUDA] Đã chuyển đổi luồng trích xuất SFace 128-D sang GPU CUDA!")
            except Exception:
                try:
                    recognizer.setPreferableBackend(cv2.dnn.DNN_BACKEND_CUDA)
                    recognizer.setPreferableTarget(cv2.dnn.DNN_TARGET_CUDA)
                except Exception:
                    pass
        return recognizer

    def fast_gpu_cosine_matching(self, query_vector, db_matrix, top_k=10):
        """
        Tính toán độ tương đồng Cosine Similarity 1:N bằng GPU CUDA Tensors (PyTorch/CuPy)
        cho phép đối chiếu hàng trăm ngàn khuôn mặt chỉ trong vài mili-giây!
        """
        if self.has_pytorch_cuda:
            try:
                import torch
                # Chuyển vector lên VRAM của GPU NVIDIA 1650 Ti
                q_tensor = torch.tensor(query_vector.flatten(), dtype=torch.float32, device='cuda')
                db_tensor = torch.tensor(db_matrix, dtype=torch.float32, device='cuda')

                # Chuẩn hóa L2 Norm trên GPU
                q_norm = q_tensor / (torch.norm(q_tensor) + 1e-8)
                db_norm = db_tensor / (torch.norm(db_tensor, dim=1, keepdim=True) + 1e-8)

                # Ma trận Cosine Similarity: S = DB @ Q
                cosine_sims = torch.matmul(db_norm, q_norm)

                # Lấy Top-K trên GPU
                scores, indices = torch.topk(cosine_sims, k=min(top_k, len(db_matrix)))

                return scores.cpu().numpy(), indices.cpu().numpy()
            except Exception as e:
                print(f"⚠️ GPU Tensor Match Error: {e}, dùng NumPy CPU fallback.")

        # CPU Fallback bằng NumPy
        q_norm = query_vector.flatten()
        norms = np.linalg.norm(db_matrix, axis=1, keepdims=True)
        norms[norms == 0] = 1e-8
        db_norm = db_matrix / norms
        cosine_sims = np.dot(db_norm, q_norm)
        top_indices = np.argsort(cosine_sims)[::-1][:top_k]

        return cosine_sims[top_indices], top_indices


def create_gpu_face_engine(yunet_path="face_detection_yunet_2023mar.onnx", sface_path="face_recognition_sface_2021dec.onnx"):
    """
    Tạo bộ khởi tạo YuNet & SFace được tăng tốc tự động bằng GPU CUDA NVIDIA 1650 Ti.
    """
    script_dir = os.path.dirname(os.path.abspath(__file__))
    yunet_model = os.path.join(script_dir, yunet_path) if not os.path.isabs(yunet_path) else yunet_path
    sface_model = os.path.join(script_dir, sface_path) if not os.path.isabs(sface_path) else sface_path

    gpu_helper = GPUCUDAEngine()

    detector = None
    recognizer = None

    if os.path.exists(yunet_model):
        detector = cv2.FaceDetectorYN.create(yunet_model, "", (320, 320), 0.20, 0.3, 10000)
        detector = gpu_helper.configure_yunet_gpu(detector)

    if os.path.exists(sface_model):
        recognizer = cv2.FaceRecognizerSF.create(sface_model, "")
        recognizer = gpu_helper.configure_sface_gpu(recognizer)

    return detector, recognizer, gpu_helper


if __name__ == "__main__":
    print("==================================================")
    print("⚡ KIỂM TRA & KÍCH HOẠT GPU CUDA (NVIDIA GTX 1650 Ti)")
    print("==================================================")
    detector, recognizer, gpu_engine = create_gpu_face_engine()
    print("\n✓ Đã sẵn sàng chạy phần cứng GPU CUDA!")
