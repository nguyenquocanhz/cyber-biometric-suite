import os
import sys
import re
import time
import urllib.parse
import requests

# Tự động cấu hình mã hóa UTF-8 cho Terminal trên Windows để tránh lỗi Unicode
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

def scrape_and_download_images(query_or_url, output_dir="xamvn_image", max_images=30):
    """
    Tự động cào và tải xuống tất cả các hình ảnh thấy được liên quan tới từ khóa hoặc liên kết Google Search URL.
    """
    script_dir = os.path.dirname(os.path.abspath(__file__))
    target_dir = os.path.join(script_dir, output_dir)

    if not os.path.exists(target_dir):
        os.makedirs(target_dir)
        print(f"📁 Đã tạo thư mục lưu trữ: {os.path.abspath(target_dir)}")

    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7"
    }

    image_urls = []

    # Kiểm tra xem người dùng truyền URL Google Search trực tiếp hay truyền từ khóa/dork
    if query_or_url.startswith("http://") or query_or_url.startswith("https://"):
        print(f"🔗 Đang xử lý liên kết Google Search trực tiếp: {query_or_url[:80]}...")
        try:
            resp = requests.get(query_or_url, headers=headers, timeout=10)
            gstatic_urls = re.findall(r'(https://encrypted-tbn0\.gstatic\.com/images\?q=tbn:[^\s"\'\\]+)', resp.text)
            raw_urls = re.findall(r'https?://[^\s"\']+\.(?:jpg|png|jpeg|webp)', resp.text, re.IGNORECASE)
            murls = re.findall(r'murl&quot;:&quot;(http[s]?://[^&"]+)&quot;', resp.text)
            
            for u in gstatic_urls + raw_urls + murls:
                if u not in image_urls and not u.startswith("https://www.google.com/search"):
                    image_urls.append(u)
        except Exception as e:
            print(f"⚠️ Lỗi khi tải URL Google Search trực tiếp: {e}")
    else:
        print(f"🔍 Đang tìm kiếm và cào hình ảnh cho từ khóa/dork: '{query_or_url}'...")
        
        # 1. Google Images Mode 2026 (udm=2)
        try:
            google_url = f"https://www.google.com/search?q={urllib.parse.quote(query_or_url)}&udm=2"
            resp_google = requests.get(google_url, headers=headers, timeout=10)
            gstatic_urls = re.findall(r'(https://encrypted-tbn0\.gstatic\.com/images\?q=tbn:[^\s"\'\\]+)', resp_google.text)
            raw_urls = re.findall(r'https?://[^\s"\']+\.(?:jpg|png|jpeg|webp)', resp_google.text, re.IGNORECASE)
            
            for g_url in gstatic_urls:
                if g_url not in image_urls:
                    image_urls.append(g_url)
            for r_url in raw_urls:
                if r_url not in image_urls and not r_url.startswith("https://www.google.com"):
                    image_urls.append(r_url)
        except Exception as e:
            print(f"⚠️ Không thể lấy ảnh từ Google Images: {e}")

        # 2. Bing Images High-Res Fallback
        try:
            bing_url = f"https://www.bing.com/images/search?q={urllib.parse.quote(query_or_url)}"
            resp_bing = requests.get(bing_url, headers=headers, timeout=10)
            murls = re.findall(r'murl&quot;:&quot;(http[s]?://[^&"]+)&quot;', resp_bing.text)
            for murl in murls:
                if murl not in image_urls:
                    image_urls.append(murl)
        except Exception as e:
            print(f"⚠️ Không thể lấy ảnh từ Bing Images: {e}")

    if not image_urls:
        print("❌ Không tìm thấy nguồn hình ảnh nào cho từ khóa/liên kết này.")
        return

    print(f"📊 Thu thập được tổng cộng {len(image_urls)} liên kết hình ảnh độc lập.")

    downloaded_count = 0
    for idx, img_url in enumerate(image_urls[:max_images]):
        try:
            ext = ".jpg"
            if ".png" in img_url.lower():
                ext = ".png"
            elif ".webp" in img_url.lower():
                ext = ".webp"

            file_name = f"xamvn_img_{downloaded_count + 1:03d}{ext}"
            file_path = os.path.join(target_dir, file_name)

            img_resp = requests.get(img_url, headers=headers, timeout=8)
            if img_resp.status_code == 200 and len(img_resp.content) > 1000:
                with open(file_path, "wb") as f:
                    f.write(img_resp.content)
                downloaded_count += 1
                print(f"   ├─ [{downloaded_count}/{min(max_images, len(image_urls))}] Tải thành công: {file_name}")
                time.sleep(0.15)
        except Exception as e:
            print(f"   ├─ ⚠️ Lỗi khi tải ảnh #{idx+1}: {e}")

    print(f"\n✨ HOÀN THÀNH! Đã tải thành công {downloaded_count} hình ảnh vào thư mục: '{os.path.abspath(target_dir)}'.")

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="Script tự động cào và tải toàn bộ hình ảnh từ Google/Bing Images")
    parser.add_argument("--query", type=str, default="xamvn", help="Từ khóa, dork hoặc URL Google Search trực tiếp (vd: https://www.google.com/search?q=...&udm=2)")
    parser.add_argument("--out", type=str, default="xamvn_image", help="Tên thư mục lưu trữ ảnh")
    parser.add_argument("--max", type=int, default=30, help="Số lượng ảnh tối đa cần tải")

    args = parser.parse_args()
    scrape_and_download_images(args.query, output_dir=args.out, max_images=args.max)
