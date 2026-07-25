#!/usr/bin/env python3
# ==============================================================================
# CYBER WEB STREAMER - HỆ THỐNG XEM PHIM QUA GIAO DIỆN WEB HTML5 (HLS.JS)
# TÍCH HỢP BỘ BỘ NHỚ ĐỆM DISK & RAM CACHE ẢNH POSTER TỰ ĐỘNG (CACHE ENGINE)
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
import mimetypes
import hashlib

PORT = 5000
USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0"

# Thư mục lưu Cache ảnh poster cục bộ trên đĩa
CACHE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), ".cache_images")
os.makedirs(CACHE_DIR, exist_ok=True)

NAS_SEARCH_PATHS = [
    "/mnt",
    "/media",
    "/volume1",
    "/storage",
    "/home",
    "D:\\",
    "E:\\",
    "F:\\"
]

MEDIA_EXTENSIONS = (
    '.mp4', '.mkv', '.mov', '.avi', '.webm', '.flv', '.ts', '.m4v',
    '.mp3', '.wav', '.flac', '.aac', '.m4a', '.ogg'
)

VIDEO_EXTS = ('.mp4', '.mkv', '.mov', '.avi', '.webm', '.flv', '.ts', '.m4v')
AUDIO_EXTS = ('.mp3', '.wav', '.flac', '.aac', '.m4a', '.ogg')

AD_KEYWORDS = [
    "bet", "88", "casino", "gamebai", "kubet", "okvip", "shbet", "f8bet", "789bet",
    "new88", "mb66", "sv388", "123b", "k9win", "hi88", "jun88", "sunwin", "b52",
    "qc_", "ad_", "banner_", "promo_", "quangcao", "789", "bk8", "w88", "fb88",
    "fun88", "188bet", "m88", "cmd368", "v9bet", "dafabet", "ricwin", "win79",
    "go88", "rienvip", "hitclub", "789club", "iwin", "rikvip"
]

def scan_nas_media():
    found_files = []
    for base_path in NAS_SEARCH_PATHS:
        if not os.path.exists(base_path):
            continue
        try:
            for root, dirs, files in os.walk(base_path, followlinks=True):
                dirs[:] = [d for d in dirs if not d.startswith('.') and d not in ['proc', 'sys', 'dev', 'vendor', 'node_modules']]
                
                for f in files:
                    ext = os.path.splitext(f)[1].lower()
                    if ext in MEDIA_EXTENSIONS and not f.startswith('.'):
                        full_path = os.path.join(root, f)
                        try:
                            size_bytes = os.path.getsize(full_path)
                            size_mb = round(size_bytes / (1024 * 1024), 1)
                            size_str = f"{round(size_mb / 1024, 2)} GB" if size_mb >= 1024 else f"{size_mb} MB"
                        except Exception:
                            size_str = "Unknown"

                        media_type = "audio" if ext in AUDIO_EXTS else "video"

                        found_files.append({
                            "name": f,
                            "path": full_path,
                            "size": size_str,
                            "ext": ext,
                            "type": media_type,
                            "folder": os.path.basename(root)
                        })
        except Exception:
            pass

    return found_files

def clean_hls_m3u8(m3u8_content, base_url):
    lines = m3u8_content.splitlines()
    cleaned_lines = []
    skip_next_segment = False

    for i in range(len(lines)):
        line = lines[i].strip()

        if line.startswith("#EXTINF"):
            if i + 1 < len(lines):
                next_line = lines[i + 1].strip().lower()
                if any(kw in next_line for kw in AD_KEYWORDS):
                    skip_next_segment = True
                    continue

        if skip_next_segment:
            skip_next_segment = False
            continue

        if line.startswith("#EXT-X-DISCONTINUITY") and i + 1 < len(lines):
            next_line = lines[i + 1].strip().lower()
            if any(kw in next_line for kw in AD_KEYWORDS):
                continue

        if line and not line.startswith("#") and not line.startswith("http"):
            line = urllib.parse.urljoin(base_url, line)

        cleaned_lines.append(line)

    return "\n".join(cleaned_lines)

# Giao diện HTML5 Cyberpunk Web Player với Tải ảnh Proxy Cục Bộ Cache
HTML_PAGE = """<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ CYBER STREAMER - DISK CACHE & HLS ANTI-AD</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --bg-dark: #0b0e14;
            --card-bg: #131822;
            --accent-cyan: #00f3ff;
            --accent-pink: #ff0055;
            --accent-green: #00ffaa;
            --accent-yellow: #ffcc00;
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

        .source-switcher { display: flex; gap: 10px; margin-bottom: 15px; }
        .source-btn {
            flex: 1; padding: 12px; background: var(--card-bg); color: var(--text-color);
            border: 1px solid #1e2638; border-radius: 8px; font-weight: bold; cursor: pointer;
            text-align: center; transition: all 0.2s ease;
        }
        .source-btn:hover { border-color: var(--accent-cyan); }
        .source-btn.active { background: var(--accent-cyan); color: #000; box-shadow: 0 0 15px rgba(0,243,255,0.3); }

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

        .filter-bar {
            display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; align-items: center;
        }
        .filter-btn {
            padding: 6px 14px; background: #182030; color: var(--muted-text);
            border: 1px solid #1e2638; border-radius: 6px; font-size: 0.8rem; font-weight: bold;
            cursor: pointer; transition: all 0.2s ease;
        }
        .filter-btn:hover { border-color: var(--accent-cyan); color: #fff; }
        .filter-btn.active { background: var(--accent-pink); color: #fff; border-color: var(--accent-pink); }

        .main-layout { display: flex; flex-direction: column; gap: 25px; }

        .player-box {
            background: var(--card-bg); padding: 20px; border-radius: 12px;
            border: 1px solid #1e2638; display: flex; flex-direction: column; gap: 15px;
        }
        .video-wrapper {
            position: relative; width: 100%; padding-top: 45%;
            background: #000; border-radius: 10px; overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        @media (max-width: 768px) { .video-wrapper { padding-top: 56.25%; } }
        video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px; }

        .grid-box {
            background: var(--card-bg); padding: 20px; border-radius: 12px;
            border: 1px solid #1e2638;
        }
        .grid-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #1e2638; padding-bottom: 10px; margin-bottom: 15px;
        }
        .grid-header h3 { font-size: 1.1rem; color: var(--accent-cyan); }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 18px;
        }

        .movie-card {
            background: #182030; border-radius: 10px; overflow: hidden;
            border: 1px solid #1e2638; cursor: pointer; transition: all 0.3s ease;
            position: relative; display: flex; flex-direction: column;
        }
        .movie-card:hover {
            transform: translateY(-5px); border-color: var(--accent-cyan);
            box-shadow: 0 8px 25px rgba(0,243,255,0.3);
        }
        .movie-card.active { border-color: var(--accent-pink); box-shadow: 0 8px 25px rgba(255,0,85,0.4); }

        .poster-wrapper {
            position: relative; width: 100%; padding-top: 145%;
            background: #0d121c; overflow: hidden;
        }
        .poster-img {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; transition: transform 0.3s ease; opacity: 1;
        }
        .movie-card:hover .poster-img { transform: scale(1.05); }

        .badge-overlay {
            position: absolute; top: 8px; left: 8px; z-index: 2;
            display: flex; flex-direction: column; gap: 4px;
        }
        .badge {
            display: inline-block; padding: 3px 8px; font-size: 0.7rem; font-weight: 800;
            border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }
        .badge-vietsub { background: var(--accent-cyan); color: #000; }
        .badge-thuyetminh { background: var(--accent-yellow); color: #000; }
        .badge-longtieng { background: var(--accent-pink); color: #fff; }
        .badge-nas { background: var(--accent-green); color: #000; }
        .badge-audio { background: #9333ea; color: #fff; }

        .episode-overlay {
            position: absolute; bottom: 8px; right: 8px; z-index: 2;
            background: rgba(0,0,0,0.85); color: #fff; padding: 2px 6px;
            font-size: 0.75rem; border-radius: 4px; font-weight: bold; border: 1px solid rgba(255,255,255,0.2);
        }

        .card-info { padding: 12px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
        .card-title { font-size: 0.9rem; font-weight: bold; color: #fff; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-subtitle { font-size: 0.75rem; color: var(--muted-text); }

        .pagination-bar {
            display: flex; justify-content: center; align-items: center; gap: 12px;
            margin-top: 25px; padding-top: 15px; border-top: 1px solid #1e2638;
        }
        .page-btn {
            padding: 8px 16px; background: #1e2638; color: var(--accent-cyan);
            border: 1px solid #1e2638; border-radius: 6px; font-weight: bold;
            cursor: pointer; transition: all 0.2s ease;
        }
        .page-btn:hover:not(:disabled) { background: var(--accent-cyan); color: #000; }
        .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .page-info { font-size: 0.9rem; font-weight: bold; color: var(--text-color); }

        .audio-tabs { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .audio-tab {
            padding: 8px 16px; background: #182030; color: var(--text-color);
            border-radius: 6px; font-size: 0.85rem; font-weight: bold; cursor: pointer;
            border: 1px solid #1e2638; transition: all 0.2s ease;
        }
        .audio-tab:hover { border-color: var(--accent-cyan); }
        .audio-tab.active { background: var(--accent-cyan); color: #000; }

        .episodes-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; max-height: 200px; overflow-y: auto; }
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
        <h1>⚡ CYBER STREAMER MEDIA PLATFORM</h1>
        <span>[ 🚀 DISK & RAM AUTO IMAGE CACHE // LOAD 1MS ]</span>
    </header>

    <div class="source-switcher">
        <div class="source-btn active" id="btnModeOnline" onclick="switchMode('online')">🌐 TÌM KIẾM ONLINE (KKPHIM)</div>
        <div class="source-btn" id="btnModeNas" onclick="switchMode('nas')">💽 BỘ SƯU TẬP NAS / HDD / SSD (/mnt)</div>
    </div>

    <div class="search-box" id="searchContainer">
        <input type="text" id="searchInput" placeholder="Nhập tên phim hoặc anime (Ví dụ: One Piece, Conan, Tây Du Ký)..." value="One Piece">
        <button onclick="searchMovie()">TÌM KIẾM PHIM</button>
    </div>

    <div class="main-layout">
        <div class="player-box">
            <h2 id="mediaTitle" style="font-size: 1.2rem; color: var(--accent-cyan);">CHỌN MỘT PHIM DƯỚI LƯỚI ĐỂ PHÁT...</h2>
            
            <div class="video-wrapper">
                <video id="videoPlayer" controls autoplay crossorigin="anonymous"></video>
            </div>

            <div style="margin-top: 10px;" id="episodesContainer">
                <h4 style="font-size: 0.95rem; color: var(--accent-yellow); margin-bottom: 8px;">🎙️ NGUỒN ÂM THANH / PHỤ ĐỀ:</h4>
                <div id="audioTabs" class="audio-tabs"><p style="color: var(--muted-text); font-size: 0.85rem;">Chưa chọn phim nào.</p></div>

                <h4 style="font-size: 0.95rem; color: var(--accent-pink); margin-bottom: 8px; margin-top: 12px;">🎬 DANH SÁCH TẬP PHIM:</h4>
                <div id="episodesList" class="episodes-grid"><p style="color: var(--muted-text); font-size: 0.85rem;">Chưa có tập nào.</p></div>
            </div>
        </div>

        <div class="grid-box">
            <div class="grid-header">
                <h3 id="gridTitle">🖼️ THƯ VIỆN BỘ PHIM ONLINE</h3>
                <div class="filter-bar" id="filterBar" style="display: none;">
                    <span style="font-size: 0.8rem; color: var(--muted-text); font-weight: bold;">Lọc loại File:</span>
                    <button class="filter-btn active" onclick="setFilter('all', this)">TẤT CẢ</button>
                    <button class="filter-btn" onclick="setFilter('video', this)">🎬 VIDEO (.mp4, .mkv, .ts, .mov)</button>
                    <button class="filter-btn" onclick="setFilter('audio', this)">🎵 ÂM THANH (.mp3, .wav, .flac)</button>
                </div>
            </div>

            <div id="movieGrid" class="movie-grid">
                <p style="color: var(--muted-text); font-size: 0.9rem;">⏳ Đang tải dữ liệu phim...</p>
            </div>

            <div class="pagination-bar">
                <button class="page-btn" id="btnPrevPage" onclick="changePage(-1)">◄ TRANG TRƯỚC</button>
                <span class="page-info" id="pageInfo">Trang 1 / 1</span>
                <button class="page-btn" id="btnNextPage" onclick="changePage(1)">TRANG SAU ►</button>
            </div>
        </div>
    </div>

    <script>
        let currentHls = null;
        let movieServers = [];
        let currentMode = 'online';

        let allItems = [];
        let filteredItems = [];
        let currentPage = 1;
        const PAGE_SIZE = 18;
        let currentFilter = 'all';

        function switchMode(mode) {
            currentMode = mode;
            currentPage = 1;
            document.querySelectorAll('.source-btn').forEach(b => b.classList.remove('active'));

            if (mode === 'online') {
                document.getElementById('btnModeOnline').classList.add('active');
                document.getElementById('searchContainer').style.display = 'flex';
                document.getElementById('gridTitle').innerText = '🖼️ THƯ VIỆN BỘ PHIM ONLINE';
                document.getElementById('episodesContainer').style.display = 'block';
                document.getElementById('filterBar').style.display = 'none';
                searchMovie();
            } else {
                document.getElementById('btnModeNas').classList.add('active');
                document.getElementById('searchContainer').style.display = 'none';
                document.getElementById('gridTitle').innerText = '💽 PHIM ĐÃ TẢI TRÊN NAS / HDD / SSD (/mnt)';
                document.getElementById('episodesContainer').style.display = 'none';
                document.getElementById('filterBar').style.display = 'flex';
                loadNasMovies();
            }
        }

        function setFilter(filterType, btnElement) {
            currentFilter = filterType;
            currentPage = 1;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            if(btnElement) btnElement.classList.add('active');

            if (filterType === 'all') {
                filteredItems = allItems;
            } else {
                filteredItems = allItems.filter(item => item.type === filterType);
            }
            renderPage();
        }

        async function searchMovie() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;

            const gridDiv = document.getElementById('movieGrid');
            gridDiv.innerHTML = '<p style="color: var(--accent-cyan); font-weight: bold;">⏳ Đang tải dữ liệu phim và ảnh Poster...</p>';

            try {
                const res = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                
                if (!data || data.length === 0) {
                    gridDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không tìm thấy kết quả phù hợp.</p>';
                    allItems = [];
                    filteredItems = [];
                    updatePaginationUI();
                    return;
                }

                allItems = data;
                filteredItems = data;
                currentPage = 1;
                renderPage();
            } catch (e) {
                gridDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi kết nối Server.</p>';
            }
        }

        async function loadNasMovies() {
            const gridDiv = document.getElementById('movieGrid');
            gridDiv.innerHTML = '<p style="color: var(--accent-cyan); font-weight: bold;">⏳ Đang tự động truy quét các file Video & Audio trong ổ đĩa NAS...</p>';

            try {
                const res = await fetch('/api/nas');
                const files = await res.json();

                if (!files || files.length === 0) {
                    gridDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Chưa tìm thấy file phim/nhạc trong các thư mục /mnt, /media.</p>';
                    allItems = [];
                    filteredItems = [];
                    updatePaginationUI();
                    return;
                }

                allItems = files;
                filteredItems = files;
                currentPage = 1;
                setFilter(currentFilter, document.querySelector(`.filter-btn.active`));
            } catch (e) {
                gridDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi nạp kho phim NAS.</p>';
            }
        }

        function renderPage() {
            const gridDiv = document.getElementById('movieGrid');
            gridDiv.innerHTML = '';

            if (filteredItems.length === 0) {
                gridDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không có file nào khớp với bộ lọc.</p>';
                updatePaginationUI();
                return;
            }

            const totalPages = Math.ceil(filteredItems.length / PAGE_SIZE);
            currentPage = Math.max(1, Math.min(currentPage, totalPages));

            const startIdx = (currentPage - 1) * PAGE_SIZE;
            const endIdx = Math.min(startIdx + PAGE_SIZE, filteredItems.length);
            const pageItems = filteredItems.slice(startIdx, endIdx);

            pageItems.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'movie-card';

                if (currentMode === 'online') {
                    let langBadgeClass = 'badge-vietsub';
                    let langText = 'VIETSUB';
                    const langLower = (item.lang || '').toLowerCase();
                    if (langLower.includes('thuyết minh')) {
                        langBadgeClass = 'badge-thuyetminh';
                        langText = 'THUYẾT MINH';
                    } else if (langLower.includes('lồng tiếng')) {
                        langBadgeClass = 'badge-longtieng';
                        langText = 'LỒNG TIẾNG';
                    }

                    // Tải qua Proxy Cache cục bộ (/proxy/image)
                    const posterProxy = item.poster_url ? `/proxy/image?url=${encodeURIComponent(item.poster_url)}` : 'https://via.placeholder.com/300x450/131822/00f3ff?text=NO+POSTER';

                    card.innerHTML = `
                        <div class="poster-wrapper">
                            <div class="badge-overlay">
                                <span class="badge ${langBadgeClass}">${langText}</span>
                            </div>
                            <span class="episode-overlay">${item.episode_current || 'HD'}</span>
                            <img class="poster-img" src="${posterProxy}" alt="${item.title}" loading="lazy" onerror="this.src='https://via.placeholder.com/300x450/131822/00f3ff?text=NO+IMAGE'">
                        </div>
                        <div class="card-info">
                            <div class="card-title">${item.title}</div>
                            <div class="card-subtitle">${item.origin_name || ''} ${item.year ? '• ' + item.year : ''}</div>
                        </div>
                    `;
                    card.onclick = () => selectMovie(item, card);
                } else {
                    const isAudio = item.type === 'audio';
                    const badgeClass = isAudio ? 'badge-audio' : 'badge-nas';
                    const badgeLabel = isAudio ? item.ext.toUpperCase() : 'LOCAL NAS';
                    const icon = isAudio ? '🎵' : '🎬';

                    card.innerHTML = `
                        <div class="poster-wrapper" style="background:#151d2a; display:flex; align-items:center; justify-content:center;">
                            <div class="badge-overlay">
                                <span class="badge ${badgeClass}">${badgeLabel}</span>
                            </div>
                            <span class="episode-overlay">${item.size}</span>
                            <div style="font-size: 3rem; color: var(--accent-cyan);">${icon}</div>
                        </div>
                        <div class="card-info">
                            <div class="card-title">${item.name}</div>
                            <div class="card-subtitle">📂 ${item.folder}</div>
                        </div>
                    `;
                    card.onclick = () => playNasFile(item, card);
                }

                gridDiv.appendChild(card);

                if (index === 0 && currentPage === 1) {
                    if (currentMode === 'online') selectMovie(item, card);
                }
            });

            updatePaginationUI();
        }

        function updatePaginationUI() {
            const totalPages = Math.ceil(filteredItems.length / PAGE_SIZE) || 1;
            document.getElementById('pageInfo').innerText = `Trang ${currentPage} / ${totalPages} (Tổng ${filteredItems.length} mục)`;
            document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
            document.getElementById('btnNextPage').disabled = (currentPage >= totalPages);
        }

        function changePage(delta) {
            currentPage += delta;
            renderPage();
            document.querySelector('.grid-box').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function playNasFile(file, cardElement) {
            document.querySelectorAll('.movie-card').forEach(c => c.classList.remove('active'));
            if(cardElement) { cardElement.classList.add('active'); cardElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }

            document.getElementById('mediaTitle').innerText = `▶ LOCAL NAS (${file.ext.toUpperCase()}): ${file.name} (${file.size})`;
            const video = document.getElementById('videoPlayer');

            if (currentHls) { currentHls.destroy(); currentHls = null; }
            
            const localUrl = `/stream/local?path=${encodeURIComponent(file.path)}`;
            video.src = localUrl;
            video.play();
        }

        async function selectMovie(item, element) {
            document.querySelectorAll('.movie-card').forEach(c => c.classList.remove('active'));
            if(element) { element.classList.add('active'); element.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }

            document.getElementById('mediaTitle').innerText = item.title;
            const audioDiv = document.getElementById('audioTabs');
            const epDiv = document.getElementById('episodesList');
            
            audioDiv.innerHTML = '<p style="color: var(--accent-cyan);">⏳ Đang nạp nguồn âm thanh...</p>';
            epDiv.innerHTML = '<p style="color: var(--accent-cyan);">⏳ Đang nạp danh sách tập...</p>';

            try {
                const res = await fetch(`/api/episodes?slug=${encodeURIComponent(item.id)}`);
                movieServers = await res.json();

                if (!movieServers || movieServers.length === 0) {
                    audioDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không có nguồn âm thanh.</p>';
                    epDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không có tập phim nào.</p>';
                    return;
                }

                audioDiv.innerHTML = '';
                movieServers.forEach((srv, idx) => {
                    const tab = document.createElement('div');
                    tab.className = `audio-tab ${idx === 0 ? 'active' : ''}`;
                    tab.innerText = srv.server_name || `Nguồn #${idx+1}`;
                    tab.onclick = () => renderServerEpisodes(idx, tab, item.title);
                    audioDiv.appendChild(tab);
                });

                renderServerEpisodes(0, audioDiv.children[0], item.title);
            } catch (e) {
                audioDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi nạp nguồn âm thanh.</p>';
                epDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Lỗi nạp tập phim.</p>';
            }
        }

        function renderServerEpisodes(serverIdx, tabElement, movieTitle) {
            document.querySelectorAll('.audio-tab').forEach(t => t.classList.remove('active'));
            if(tabElement) tabElement.classList.add('active');

            const epDiv = document.getElementById('episodesList');
            const server = movieServers[serverIdx];

            if (!server || !server.server_data || server.server_data.length === 0) {
                epDiv.innerHTML = '<p style="color: var(--accent-pink);">❌ Không có tập phim nào trên nguồn này.</p>';
                return;
            }

            epDiv.innerHTML = '';
            server.server_data.forEach((ep, idx) => {
                const btn = document.createElement('button');
                btn.className = 'ep-btn';
                btn.innerText = ep.name || `Tập ${idx+1}`;
                btn.onclick = () => playStream(ep.link_m3u8, `${movieTitle} - [${server.server_name}] - ${ep.name}`, btn);
                epDiv.appendChild(btn);
            });

            if (server.server_data.length > 0) {
                playStream(server.server_data[0].link_m3u8, `${movieTitle} - [${server.server_name}] - ${server.server_data[0].name}`, epDiv.children[0]);
            }
        }

        function playStream(rawUrl, title, btnElement) {
            document.querySelectorAll('.ep-btn').forEach(b => b.classList.remove('active'));
            if(btnElement) btnElement.classList.add('active');

            document.getElementById('mediaTitle').innerText = `▶ ${title} [🛡️ ADS BLOCKED]`;
            const video = document.getElementById('videoPlayer');

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

        elif path == "/proxy/image":
            img_url = query_params.get("url", [""])[0]
            if img_url:
                url_hash = hashlib.md5(img_url.encode('utf-8')).hexdigest()
                cached_file = os.path.join(CACHE_DIR, f"{url_hash}.jpg")

                # Kiểm tra nếu ảnh ĐÃ CÓ trong Disk Cache cục bộ -> Đọc trực tiếp trong 1 millisecond!
                if os.path.exists(cached_file):
                    try:
                        with open(cached_file, 'rb') as f:
                            img_data = f.read()
                        self.send_response(200)
                        self.send_header("Content-Type", "image/jpeg")
                        self.send_header("Cache-Control", "public, max-age=2592000") # Cache trình duyệt 30 ngày
                        self.send_header("X-Cache-Status", "HIT_LOCAL_DISK")
                        self.send_header("Access-Control-Allow-Origin", "*")
                        self.end_headers()
                        self.wfile.write(img_data)
                        return
                    except Exception:
                        pass

                # Nếu chưa có -> Tải từ Server về và lưu vào Disk Cache
                try:
                    req = urllib.request.Request(img_url, headers={
                        "User-Agent": USER_AGENT,
                        "Referer": "https://phimapi.com/"
                    })
                    with urllib.request.urlopen(req, timeout=6) as resp:
                        img_data = resp.read()
                        content_type = resp.headers.get("Content-Type", "image/jpeg")

                        # Ghi vào Disk Cache
                        try:
                            with open(cached_file, 'wb') as f:
                                f.write(img_data)
                        except Exception:
                            pass

                        self.send_response(200)
                        self.send_header("Content-Type", content_type)
                        self.send_header("Cache-Control", "public, max-age=2592000")
                        self.send_header("X-Cache-Status", "MISS_FETCHED")
                        self.send_header("Access-Control-Allow-Origin", "*")
                        self.end_headers()
                        self.wfile.write(img_data)
                        return
                except Exception:
                    pass

            self.send_response(200)
            self.send_header("Content-Type", "image/gif")
            self.end_headers()
            self.wfile.write(b'GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;')

        elif path == "/api/nas":
            files = scan_nas_media()
            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
            self.wfile.write(json.dumps(files).encode('utf-8'))

        elif path == "/stream/local":
            file_path = query_params.get("path", [""])[0]
            if file_path and os.path.exists(file_path):
                file_size = os.path.getsize(file_path)
                mime_type, _ = mimetypes.guess_type(file_path)
                if not mime_type:
                    mime_type = "video/mp4"

                range_header = self.headers.get('Range')
                if range_header:
                    match = re.search(r'bytes=(\d+)-(\d*)', range_header)
                    if match:
                        start = int(match.group(1))
                        end = int(match.group(2)) if match.group(2) else file_size - 1
                        length = end - start + 1

                        self.send_response(206)
                        self.send_header('Content-Type', mime_type)
                        self.send_header('Content-Range', f'bytes {start}-{end}/{file_size}')
                        self.send_header('Content-Length', str(length))
                        self.send_header('Accept-Ranges', 'bytes')
                        self.end_headers()

                        with open(file_path, 'rb') as f:
                            f.seek(start)
                            chunk_size = 64 * 1024
                            bytes_to_send = length
                            while bytes_to_send > 0:
                                chunk = f.read(min(chunk_size, bytes_to_send))
                                if not chunk:
                                    break
                                self.wfile.write(chunk)
                                bytes_to_send -= len(chunk)
                        return

                self.send_response(200)
                self.send_header('Content-Type', mime_type)
                self.send_header('Content-Length', str(file_size))
                self.send_header('Accept-Ranges', 'bytes')
                self.end_headers()

                with open(file_path, 'rb') as f:
                    chunk_size = 64 * 1024
                    while True:
                        chunk = f.read(chunk_size)
                        if not chunk:
                            break
                        self.wfile.write(chunk)
                return

            self.send_response(404)
            self.end_headers()

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
                except Exception:
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
                                poster_rel = item.get('poster_url') or item.get('thumb_url') or ""
                                if poster_rel:
                                    if poster_rel.startswith("http://") or poster_rel.startswith("https://"):
                                        poster_full = poster_rel
                                    else:
                                        poster_full = f"https://phimimg.com/{poster_rel.lstrip('/')}"
                                else:
                                    poster_full = ""

                                results.append({
                                    "id": item.get("slug"),
                                    "title": item.get('name'),
                                    "origin_name": item.get('origin_name'),
                                    "year": item.get('year'),
                                    "poster_url": poster_full,
                                    "lang": item.get("lang", "Vietsub"),
                                    "episode_current": item.get("episode_current", "HD"),
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
            servers = []
            if slug:
                url = f"https://phimapi.com/phim/{slug}"
                try:
                    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
                    with urllib.request.urlopen(req, timeout=8) as resp:
                        if resp.status == 200:
                            data = json.loads(resp.read().decode('utf-8'))
                            servers = data.get("episodes", [])
                except Exception:
                    pass

            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
            self.wfile.write(json.dumps(servers).encode('utf-8'))
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
    print("  🚀 CYBER WEB STREAMER HAS LAUNCHED (AUTO DISK & RAM IMAGE CACHE ACTIVE)!")
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
