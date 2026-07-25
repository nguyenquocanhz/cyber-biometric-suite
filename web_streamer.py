#!/usr/bin/env python3
# ==============================================================================
# CYBER WEB STREAMER - HỆ THỐNG XEM PHIM QUA GIAO DIỆN WEB HTML5 (HLS.JS)
# TÍCH HỢP BỘ LỌC TỰ ĐỘNG CHẶN QUẢNG CÁO GAME BÀI / CASINO TRONG HLS M3U8
# Chạy Web Server tại http://IP-HOMELAB:5000 (Xem trên Laptop / Điện thoại / Tablet)
# ==============================================================================

import os
import sys
import json
import re
import urllib.parse
import urllib.request
from http.server import HTTPServer, BaseHTTPRequestHandler
import socket

PORT = 5000
USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0"

# Danh sách từ khóa nhận diện Quảng cáo Game bài / Casino / Nhà cái để lọc bỏ khỏi HLS .m3u8
AD_KEYWORDS = [
    "bet", "88", "casino", "gamebai", "kubet", "okvip", "shbet", "f8bet", "789bet",
    "new88", "mb66", "sv388", "123b", "k9win", "hi88", "jun88", "sunwin", "b52",
    "qc_", "ad_", "banner_", "promo_", "quangcao", "789", "bk8", "w88", "fb88",
    "fun88", "188bet", "m88", "cmd368", "v9bet", "dafabet", "ricwin", "win79",
    "go88", "rienvip", "hitclub", "789club", "iwin", "rikvip"
]

def clean_hls_m3u8(m3u8_content, base_url):
    """
    Phân tích & Lọc bỏ toàn bộ các phân đoạn (Segment .ts) chứa Quảng cáo Game Bài khỏi file HLS .m3u8
    """
    lines = m3u8_content.splitlines()
    cleaned_lines = []
    skip_next_segment = False

    for i in range(len(lines)):
        line = lines[i].strip()

        # Kiểm tra thẻ thời lượng phân đoạn #EXTINF
        if line.startswith("#EXTINF"):
            # Kiểm tra dòng URL phân đoạn kế tiếp
            if i + 1 < len(lines):
                next_line = lines[i + 1].strip().lower()
                # Nếu URL kế tiếp chứa từ khóa Quảng cáo Game bài -> Đánh dấu bỏ qua phân đoạn này
                if any(kw in next_line for kw in AD_KEYWORDS):
                    skip_next_segment = True
                    continue

        if skip_next_segment:
            skip_next_segment = False
            continue

        # Lọc các thẻ phân tách quảng cáo #EXT-X-DISCONTINUITY thừa
        if line.startswith("#EXT-X-DISCONTINUITY") and i + 1 < len(lines):
            next_line = lines[i + 1].strip().lower()
            if any(kw in next_line for kw in AD_KEYWORDS):
                continue

        # Chuẩn hóa đường dẫn tuyệt đối cho các phân đoạn .ts
        if line and not line.startswith("#") and not line.startswith("http"):
            line = urllib.parse.urljoin(base_url, line)

        cleaned_lines.append(line)

    return "\n".join(cleaned_lines)

# Giao diện HTML5 Cyberpunk Web Player
HTML_PAGE = """<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ CYBER STREAMER WEB PLAYER - NO ADS</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --bg-dark: #0b0e14;
            --card-bg: #131822;
            --accent-cyan: #00f3ff;
            --accent-pink: #ff0055;
            --accent-green: #00ffaa;
            --text-color: #e2e8f0;
            --muted-text: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-color); padding: 20px; }
        
        header {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--card-bg); padding: 15px 25px; border-radius: 12px;
            border: 1px solid rgba(0,243,255,0.2); margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,243,255,0.1);
        }
        header h1 { font-size: 1.4rem; color: var(--accent-cyan); font-weight: 800; letter-spacing: 1px; }
        header span { font-size: 0.85rem; color: var(--accent-green); font-family: monospace; font-weight: bold; }

        .search-box {
            display: flex; gap: 10px; background: var(--card-bg); padding: 15px;
            border-radius: 12px; border: 1px solid #1e2638; margin-bottom: 20px;
        }
        input[type="text"] {
            flex: 1; padding: 12px 18px; background: #1e2638; border: none;
            border-radius: 8px; color: #fff; font-size: 1rem; outline: none;
        }
        button {
            padding: 12px 24px; background: var(--accent-cyan); color: #000;
            font-weight: bold; border: none; border-radius: 8px; cursor: pointer;
            transition: all 0.2s ease;
        }
        button:hover { background: #80fcff; box-shadow: 0 0 15px rgba(0,243,255,0.4); }

        .main-container { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        @media (max-width: 900px) { .main-container { grid-template-columns: 1fr; } }

        .results-box {
            background: var(--card-bg); padding: 15px; border-radius: 12px;
            border: 1px solid #1e2638; max-height: 650px; overflow-y: auto;
        }
        .results-box h3 { font-size: 1rem; color: var(--accent-cyan); margin-bottom: 12px; border-bottom: 1px solid #1e2638; padding-bottom: 8px; }
        .media-item {
            padding: 12px; background: #182030; border-radius: 8px; margin-bottom: 10px;
            cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent;
        }
        .media-item:hover { border-color: var(--accent-cyan); background: #1f2a3f; }
        .media-item.active { border-color: var(--accent-pink); background: #261a28; }
        .media-item h4 { font-size: 0.95rem; color: #fff; margin-bottom: 4px; }
        .media-item p { font-size: 0.8rem; color: var(--muted-text); }

        .player-box {
            background: var(--card-bg); padding: 20px; border-radius: 12px;
            border: 1px solid #1e2638; display: flex; flex-direction: column; gap: 15px;
        }
        .video-wrapper {
            position: relative; width: 100%; padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: #000; border-radius: 10px; overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px; }

        .episodes-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .ep-btn {
            padding: 8px 16px; background: #1e2638; color: var(--accent-cyan);
            border-radius: 6px; font-size: 0.85rem; font-weight: bold; cursor: pointer;
            border: 1px solid transparent; transition: all 0.2s ease;
        }
        .ep-btn:hover { background: var(--accent-cyan); color: #000; }
        .ep-btn.active { background: var(--accent-pink); color: #fff; }
    </style>
</head>
<body>
    <header>
        <h1>⚡ CYBER STREAMER WEB PLAYER</h1>
        <span>[ 🛡️ HLS ANTI-AD FILTER // CHẶN QUẢNG CÁO GAME BÀI ]</span>
    </header>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Nhập tên phim hoặc anime (Ví dụ: One Piece, Conan, Naruto)..." value="One Piece">
        <button onclick="searchMovie()">TÌM KIẾM</button>
    </div>

    <div class="main-container">
        <div class="results-box">
            <h3>📺 KẾT QUẢ TÌM KIẾM</h3>
            <div id="resultsList"><p style="color: var(--muted-text); font-size: 0.9rem;">Nhập từ khóa và bấm Tìm kiếm...</p></div>
        </div>

        <div class="player-box">
            <h2 id="mediaTitle" style="font-size: 1.2rem; color: var(--accent-cyan);">ĐANG CHỜ CHỌN PHIM...</h2>
            
            <div class="video-wrapper">
                <video id="videoPlayer" controls autoplay crossorigin="anonymous"></video>
            </div>

            <div style="margin-top: 10px;">
                <h4 style="font-size: 0.95rem; color: var(--accent-pink); margin-bottom: 8px;">🎬 DANH SÁCH TẬP PHIM:</h4>
                <div id="episodesList" class="episodes-grid"><p style="color: var(--muted-text); font-size: 0.85rem;">Chưa có tập nào.</p></div>
            </div>
        </div>
    </div>

    <script>
        let currentHls = null;

        async function searchMovie() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;

            const resultsDiv = document.getElementById('resultsList');
            resultsDiv.innerHTML = '<p style="color: var(--accent-cyan);">⏳ Đang tìm kiếm dữ liệu...</p>';

            try {
                const res = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                
                if (!data || data.length === 0) {
                    resultsDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không tìm thấy kết quả.</p>';
                    return;
                }

                resultsDiv.innerHTML = '';
                data.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'media-item';
                    el.innerHTML = `<h4>${item.title}</h4><p>Server: ${item.source.toUpperCase()}</p>`;
                    el.onclick = () => selectMovie(item, el);
                    resultsDiv.appendChild(el);
                });
            } catch (e) {
                resultsDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi kết nối Server.</p>';
            }
        }

        async function selectMovie(item, element) {
            document.querySelectorAll('.media-item').forEach(e => e.classList.remove('active'));
            if(element) element.classList.add('active');

            document.getElementById('mediaTitle').innerText = item.title;
            const epDiv = document.getElementById('episodesList');
            epDiv.innerHTML = '<p style="color: var(--accent-cyan);">⏳ Đang nạp danh sách tập...</p>';

            try {
                const res = await fetch(`/api/episodes?slug=${encodeURIComponent(item.id)}`);
                const episodes = await res.json();

                if (!episodes || episodes.length === 0) {
                    epDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không có tập phim nào.</p>';
                    return;
                }

                epDiv.innerHTML = '';
                episodes.forEach((ep, idx) => {
                    const btn = document.createElement('button');
                    btn.className = 'ep-btn';
                    btn.innerText = ep.name || `Tập ${idx+1}`;
                    btn.onclick = () => playStream(ep.link_m3u8, `${item.title} - ${ep.name}`, btn);
                    epDiv.appendChild(btn);
                });

                // Auto play first episode
                if (episodes.length > 0) {
                    playStream(episodes[0].link_m3u8, `${item.title} - ${episodes[0].name}`, epDiv.children[0]);
                }
            } catch (e) {
                epDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi nạp tập phim.</p>';
            }
        }

        function playStream(rawUrl, title, btnElement) {
            document.querySelectorAll('.ep-btn').forEach(b => b.classList.remove('active'));
            if(btnElement) btnElement.classList.add('active');

            document.getElementById('mediaTitle').innerText = `▶ ${title} [🛡️ ADS BLOCKED]`;
            const video = document.getElementById('videoPlayer');

            // Tự động định tuyến qua HLS Anti-Ad Proxy sạch
            const cleanUrl = `/proxy/hls?url=${encodeURIComponent(rawUrl)}`;

            if (Hls.isSupported()) {
                if (currentHls) { currentHls.destroy(); }
                currentHls = new Hls();
                currentHls.loadSource(cleanUrl);
                currentHls.attachMedia(video);
                currentHls.on(Hls.Events.MANIFEST_PARSED, function() { video.play(); });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = cleanUrl;
                video.play();
            }
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchMovie();
        });

        // Trigger default search on load
        searchMovie();
    </script>
</body>
</html>
"""

class CyberStreamerHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query_params = urllib.parse.parse_qs(parsed.query)

        if path == "/" or path == "/index.html":
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.end_headers()
            self.wfile.write(HTML_PAGE.encode('utf-8'))

        elif path == "/proxy/hls":
            target_url = query_params.get("url", [""])[0]
            if target_url:
                try:
                    req = urllib.request.Request(target_url, headers={"User-Agent": USER_AGENT})
                    with urllib.request.urlopen(req, timeout=10) as resp:
                        content = resp.read().decode('utf-8', errors='ignore')
                        clean_content = clean_hls_m3u8(content, target_url)

                        self.send_response(200)
                        self.send_header("Content-Type", "application/vnd.apple.mpegurl")
                        self.send_header("Access-Control-Allow-Origin", "*")
                        self.end_headers()
                        self.wfile.write(clean_content.encode('utf-8'))
                        return
                except Exception as e:
                    pass

            self.send_response(500)
            self.end_headers()

        elif path == "/api/search":
            q = query_params.get("q", [""])[0]
            results = []
            if q:
                url = f"https://phimapi.com/v1/api/tim-kiem?keyword={urllib.parse.quote(q)}"
                try:
                    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
                    with urllib.request.urlopen(req, timeout=8) as resp:
                        if resp.status == 200:
                            data = json.loads(resp.read().decode('utf-8'))
                            items = data.get("data", {}).get("items", [])
                            for item in items:
                                results.append({
                                    "id": item.get("slug"),
                                    "title": f"{item.get('name')} ({item.get('origin_name')} - {item.get('year')})",
                                    "source": "kkphim"
                                })
                except Exception:
                    pass

            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
            self.wfile.write(json.dumps(results).encode('utf-8'))

        elif path == "/api/episodes":
            slug = query_params.get("slug", [""])[0]
            episodes = []
            if slug:
                url = f"https://phimapi.com/phim/{slug}"
                try:
                    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
                    with urllib.request.urlopen(req, timeout=8) as resp:
                        if resp.status == 200:
                            data = json.loads(resp.read().decode('utf-8'))
                            eps = data.get("episodes", [])
                            if eps:
                                episodes = eps[0].get("server_data", [])
                except Exception:
                    pass

            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
            self.wfile.write(json.dumps(episodes).encode('utf-8'))
        else:
            self.send_response(404)
            self.end_headers()

def get_ip_address():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

def run():
    local_ip = get_ip_address()
    server_address = ('', PORT)
    httpd = HTTPServer(server_address, CyberStreamerHandler)

    print("\033[96m\033[1m")
    print("  🛡️ CYBER WEB STREAMER HAS LAUNCHED (ANTI-AD FILTER ENGINE ACTIVE)!")
    print("  -------------------------------------------------------------")
    print(f"  👉 Truy cập trên Laptop / Điện thoại: \033[92mhttp://{local_ip}:{PORT}\033[96m")
    print(f"  👉 Truy cập tại máy Homelab local:   \033[92mhttp://localhost:{PORT}\033[96m")
    print("  -------------------------------------------------------------\033[0m\n")

    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n\033[91mĐã dừng Web Server.\033[0m")

if __name__ == "__main__":
    run()
