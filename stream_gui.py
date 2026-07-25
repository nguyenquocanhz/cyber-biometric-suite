#!/usr/bin/env python3
# ==============================================================================
# CYBER MEDIA STREAMER GUI - CYBERPUNK HUD INTERFACE
# Giao diện đồ họa Futurist Neon cho phép Tìm kiếm & Xem Phim Vietsub / Anime
# ==============================================================================

import os
import sys
import threading
import subprocess
import urllib.parse
import json
import requests

# Xử lý tùy chọn Pillow (PIL) - Tự động Fallback nếu chưa cài
try:
    from PIL import Image, ImageTk
    HAS_PIL = True
except ImportError:
    HAS_PIL = False

# Thử nghiệm khởi tạo Tkinter với bảo vệ không crash ở môi trường Headless Linux
try:
    import tkinter as tk
    from tkinter import ttk, messagebox
    HAS_TKINTER = True
except ImportError:
    HAS_TKINTER = False

# Cấu hình mã hóa UTF-8
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

if HAS_TKINTER:
    class CyberStreamerGUI(tk.Tk):
        def __init__(self):
            super().__init__()

            self.title("⚡ CYBER MEDIA STREAMER GUI v3.0 - NEON HUD")
            self.geometry("1050x680")
            self.minsize(850, 550)
            self.configure(bg="#0b0e14")

            # Color Palette
            self.BG_DARK = "#0b0e14"
            self.CARD_BG = "#131822"
            self.ACCENT_CYAN = "#00f3ff"
            self.ACCENT_PINK = "#ff0055"
            self.TEXT_COLOR = "#e2e8f0"
            self.MUTED_TEXT = "#94a3b8"

            self.headers = {
                "User-Agent": "Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0",
                "Accept": "application/json"
            }

            self.search_results = []
            self.current_episodes = []
            self.selected_media = None

            self.setup_ui()

        def setup_ui(self):
            # Header Bar
            header = tk.Frame(self, bg=self.CARD_BG, height=60, bd=1, relief="solid")
            header.pack(fill="x", side="top")

            title_lbl = tk.Label(
                header,
                text="⚡ CYBER MEDIA STREAMER",
                font=("Segoe UI", 16, "bold"),
                fg=self.ACCENT_CYAN,
                bg=self.CARD_BG
            )
            title_lbl.pack(side="left", padx=20, pady=12)

            subtitle_lbl = tk.Label(
                header,
                text="[ KKPHIM VIETSUB & GOGOANIME STREAM ENGINE ]",
                font=("Consolas", 10, "bold"),
                fg=self.ACCENT_PINK,
                bg=self.CARD_BG
            )
            subtitle_lbl.pack(side="left", padx=5)

            # Main Layout (Split Pane)
            main_frame = tk.Frame(self, bg=self.BG_DARK)
            main_frame.pack(fill="both", expand=True, padx=15, pady=15)

            # Left Column: Search & Movie List
            left_col = tk.Frame(main_frame, bg=self.BG_DARK, width=420)
            left_col.pack(side="left", fill="both", expand=False, padx=(0, 10))

            # Search Bar Box
            search_box = tk.LabelFrame(left_col, text=" 🔍 SEARCH ENGINE ", font=("Segoe UI", 10, "bold"), fg=self.ACCENT_CYAN, bg=self.CARD_BG, bd=1, relief="solid")
            search_box.pack(fill="x", side="top", pady=(0, 10))

            search_input_frame = tk.Frame(search_box, bg=self.CARD_BG)
            search_input_frame.pack(fill="x", padx=10, pady=10)

            self.entry_query = tk.Entry(
                search_input_frame,
                font=("Segoe UI", 11),
                bg="#1e2638",
                fg="#ffffff",
                insertbackground=self.ACCENT_CYAN,
                bd=0,
                relief="flat"
            )
            self.entry_query.pack(side="left", fill="x", expand=True, ipady=6, padx=(0, 10))
            self.entry_query.bind("<Return>", lambda event: self.start_search())
            self.entry_query.insert(0, "One Piece")

            btn_search = tk.Button(
                search_input_frame,
                text="TÌM KIẾM",
                font=("Segoe UI", 10, "bold"),
                bg=self.ACCENT_CYAN,
                fg="#000000",
                activebackground="#80fcff",
                bd=0,
                cursor="hand2",
                command=self.start_search
            )
            btn_search.pack(side="right", ipadx=12, ipady=4)

            # Server Selector Frame
            server_frame = tk.Frame(search_box, bg=self.CARD_BG)
            server_frame.pack(fill="x", padx=10, pady=(0, 10))

            tk.Label(server_frame, text="Nguồn Server:", font=("Segoe UI", 9, "bold"), fg=self.MUTED_TEXT, bg=self.CARD_BG).pack(side="left")

            self.server_var = tk.StringVar(value="kkphim")
            rb_kk = tk.Radiobutton(server_frame, text="KKPhim (Vietsub)", variable=self.server_var, value="kkphim", bg=self.CARD_BG, fg=self.ACCENT_CYAN, selectcolor=self.BG_DARK, activebackground=self.CARD_BG)
            rb_kk.pack(side="left", padx=10)

            rb_gogo = tk.Radiobutton(server_frame, text="GogoAnime (Engsub)", variable=self.server_var, value="gogoanime", bg=self.CARD_BG, fg=self.ACCENT_PINK, selectcolor=self.BG_DARK, activebackground=self.CARD_BG)
            rb_gogo.pack(side="left")

            # Results Listbox
            list_box = tk.LabelFrame(left_col, text=" 📺 KẾT QUẢ TÌM KIẾM ", font=("Segoe UI", 10, "bold"), fg=self.ACCENT_CYAN, bg=self.CARD_BG, bd=1, relief="solid")
            list_box.pack(fill="both", expand=True)

            self.lst_results = tk.Listbox(
                list_box,
                font=("Segoe UI", 10),
                bg="#182030",
                fg=self.TEXT_COLOR,
                selectbackground=self.ACCENT_CYAN,
                selectforeground="#000000",
                bd=0,
                highlightthickness=0
            )
            self.lst_results.pack(fill="both", expand=True, padx=10, pady=10)
            self.lst_results.bind("<<ListboxSelect>>", self.on_media_select)

            # Right Column: Details & Episode Buttons
            right_col = tk.Frame(main_frame, bg=self.CARD_BG, bd=1, relief="solid")
            right_col.pack(side="right", fill="both", expand=True)

            # Details Header
            self.lbl_media_title = tk.Label(
                right_col,
                text="CHỌN MỘT PHIM ĐỂ XEM CHI TIẾT",
                font=("Segoe UI", 13, "bold"),
                fg=self.ACCENT_CYAN,
                bg=self.CARD_BG,
                anchor="w",
                wraplength=500
            )
            self.lbl_media_title.pack(fill="x", padx=15, pady=(15, 5))

            self.lbl_media_status = tk.Label(
                right_col,
                text="Trạng thái: Sẵn sàng",
                font=("Segoe UI", 9),
                fg=self.MUTED_TEXT,
                bg=self.CARD_BG,
                anchor="w"
            )
            self.lbl_media_status.pack(fill="x", padx=15, pady=(0, 15))

            # Info Frame
            info_frame = tk.Frame(right_col, bg=self.CARD_BG)
            info_frame.pack(fill="x", padx=15, pady=(0, 15))

            self.lbl_description = tk.Label(
                info_frame,
                text="Nhấp chọn phim trong danh sách bên trái để lấy tập phim và khởi chạy stream video HD mượt mà qua MPV Player.",
                font=("Segoe UI", 9),
                fg=self.TEXT_COLOR,
                bg=self.CARD_BG,
                justify="left",
                wraplength=480
            )
            self.lbl_description.pack(side="top", fill="both", expand=True)

            # Episodes Frame
            ep_box = tk.LabelFrame(right_col, text=" 🎬 DANH SÁCH TẬP PHIM ", font=("Segoe UI", 10, "bold"), fg=self.ACCENT_PINK, bg=self.CARD_BG, bd=1, relief="solid")
            ep_box.pack(fill="both", expand=True, padx=15, pady=(0, 15))

            # Canvas & Scrollbar for Episodes Grid
            self.ep_canvas = tk.Canvas(ep_box, bg="#131822", bd=0, highlightthickness=0)
            self.ep_scrollbar = ttk.Scrollbar(ep_box, orient="vertical", command=self.ep_canvas.yview)
            self.ep_grid_frame = tk.Frame(self.ep_canvas, bg="#131822")

            self.ep_grid_frame.bind("<Configure>", lambda e: self.ep_canvas.configure(scrollregion=self.ep_canvas.bbox("all")))
            self.ep_canvas.create_window((0, 0), window=self.ep_grid_frame, anchor="nw")
            self.ep_canvas.configure(yscrollcommand=self.ep_scrollbar.set)

            self.ep_canvas.pack(side="left", fill="both", expand=True, padx=5, pady=5)
            self.ep_scrollbar.pack(side="right", fill="y")

            # Bottom Status Bar
            status_bar = tk.Frame(self, bg="#080a0f", height=25)
            status_bar.pack(fill="x", side="bottom")

            self.lbl_status = tk.Label(status_bar, text="⚡ SYSTEM READY // ONLINE", font=("Consolas", 8, "bold"), fg=self.ACCENT_CYAN, bg="#080a0f")
            self.lbl_status.pack(side="left", padx=10)

        def set_status(self, msg):
            self.lbl_status.config(text=f"⚡ {msg.upper()}")

        def start_search(self):
            query = self.entry_query.get().strip()
            if not query:
                return

            self.set_status(f"Đang tìm kiếm '{query}'...")
            self.lst_results.delete(0, tk.END)
            self.lst_results.insert(tk.END, "⏳ Đang tải dữ liệu...")

            threading.Thread(target=self._async_search, args=(query,), daemon=True).start()

        def _async_search(self, query):
            server = self.server_var.get()
            results = []

            if server == "kkphim":
                url = f"https://phimapi.com/v1/api/tim-kiem?keyword={urllib.parse.quote(query)}"
                try:
                    resp = requests.get(url, headers=self.headers, timeout=8)
                    if resp.status_code == 200:
                        data = resp.json()
                        items = data.get("data", {}).get("items", [])
                        for item in items:
                            results.append({
                                "id": item.get("slug"),
                                "title": f"{item.get('name')} ({item.get('origin_name')} - {item.get('year')})",
                                "source": "kkphim"
                            })
                except Exception:
                    pass
            else:
                url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/{urllib.parse.quote(query)}"
                try:
                    resp = requests.get(url, headers=self.headers, timeout=8)
                    if resp.status_code == 200:
                        data = resp.json()
                        for r in data.get("results", []):
                            results.append({
                                "id": r.get("id"),
                                "title": f"{r.get('title')} (EngSub)",
                                "source": "gogoanime"
                            })
                except Exception:
                    pass

            self.search_results = results
            self.after(0, self._render_search_results)

        def _render_search_results(self):
            self.lst_results.delete(0, tk.END)
            if not self.search_results:
                self.lst_results.insert(tk.END, "❌ Không tìm thấy kết quả nào.")
                self.set_status("Không tìm thấy kết quả.")
                return

            for item in self.search_results:
                self.lst_results.insert(tk.END, f" 🎬 {item['title']}")

            self.set_status(f"Tìm thấy {len(self.search_results)} kết quả.")

        def on_media_select(self, event):
            selection = self.lst_results.curselection()
            if not selection:
                return

            idx = selection[0]
            if idx >= len(self.search_results):
                return

            selected = self.search_results[idx]
            self.selected_media = selected

            self.lbl_media_title.config(text=selected['title'])
            self.lbl_media_status.config(text=f"Server: {selected['source'].upper()} | ID: {selected['id']}")

            # Clear existing episode buttons
            for widget in self.ep_grid_frame.winfo_children():
                widget.destroy()

            self.set_status(f"Đang lấy danh sách tập cho '{selected['title']}'...")
            threading.Thread(target=self._async_load_episodes, args=(selected,), daemon=True).start()

        def _async_load_episodes(self, media):
            episodes = []
            if media['source'] == "kkphim":
                url = f"https://phimapi.com/phim/{media['id']}"
                try:
                    resp = requests.get(url, headers=self.headers, timeout=8)
                    if resp.status_code == 200:
                        data = resp.json()
                        eps = data.get("episodes", [])
                        if eps:
                            episodes = eps[0].get("server_data", [])
                except Exception:
                    pass
            else:
                url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/info/{media['id']}"
                try:
                    resp = requests.get(url, headers=self.headers, timeout=8)
                    if resp.status_code == 200:
                        data = resp.json()
                        episodes = data.get("episodes", [])
                except Exception:
                    pass

            self.current_episodes = episodes
            self.after(0, self._render_episodes)

        def _render_episodes(self):
            for widget in self.ep_grid_frame.winfo_children():
                widget.destroy()

            if not self.current_episodes:
                tk.Label(self.ep_grid_frame, text="❌ Không tìm thấy tập phim.", fg=self.ACCENT_PINK, bg="#131822").pack(pady=20)
                self.set_status("Không lấy được tập phim.")
                return

            cols = 5
            for idx, ep in enumerate(self.current_episodes):
                row = idx // cols
                col = idx % cols

                ep_name = ep.get("name") if self.selected_media['source'] == "kkphim" else f"Tập {ep.get('number')}"

                btn = tk.Button(
                    self.ep_grid_frame,
                    text=ep_name,
                    font=("Segoe UI", 9, "bold"),
                    bg="#1e2638",
                    fg=self.ACCENT_CYAN,
                    activebackground=self.ACCENT_CYAN,
                    activeforeground="#000000",
                    bd=1,
                    relief="flat",
                    cursor="hand2",
                    width=10,
                    command=lambda e=ep: self.play_episode(e)
                )
                btn.grid(row=row, column=col, padx=5, pady=5)

            self.set_status(f"Sẵn sàng xem {len(self.current_episodes)} tập.")

        def play_episode(self, ep):
            media = self.selected_media
            if not media:
                return

            if media['source'] == "kkphim":
                stream_url = ep.get("link_m3u8")
                ep_title = f"{media['title']} - {ep.get('name')}"
                if stream_url:
                    self.launch_mpv(stream_url, ep_title)
            else:
                ep_id = ep.get("id")
                self.set_status(f"Đang lấy luồng m3u8 cho tập {ep.get('number')}...")
                threading.Thread(target=self._async_play_gogoanime, args=(ep_id, f"{media['title']} - Tập {ep.get('number')}"), daemon=True).start()

        def _async_play_gogoanime(self, ep_id, ep_title):
            url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/watch/{ep_id}"
            stream_url = None
            try:
                resp = requests.get(url, headers=self.headers, timeout=8)
                if resp.status_code == 200:
                    sources = resp.json().get("sources", [])
                    for s in sources:
                        if s.get("quality") in ["default", "1080p", "720p"]:
                            stream_url = s.get("url")
                            break
                    if not stream_url and sources:
                        stream_url = sources[0].get("url")
            except Exception:
                pass

            if stream_url:
                self.after(0, lambda: self.launch_mpv(stream_url, ep_title))
            else:
                self.after(0, lambda: messagebox.showerror("Lỗi", "Không thể lấy luồng stream cho tập này!"))

        def launch_mpv(self, stream_url, title):
            self.set_status(f"▶ Đang phát: {title}")

            cmd = [
                "mpv",
                f"--force-media-title={title}",
                "--geometry=1280x720",
                "--no-ytdl",
                f"--user-agent={self.headers['User-Agent']}",
                "--referrer=https://phimapi.com/",
                stream_url
            ]

            def _run():
                try:
                    subprocess.run(cmd)
                except FileNotFoundError:
                    messagebox.showerror("Lỗi Trình Phát", "Không tìm thấy trình phát MPV trên máy!\nHãy cài đặt mpv: sudo apt install mpv")

            threading.Thread(target=_run, daemon=True).start()

if __name__ == "__main__":
    if not os.environ.get("DISPLAY") and sys.platform.startswith("linux"):
        print("\033[91m❌ Lỗi: Bạn đang chạy trên môi trường Linux Terminal thuần (Headless / SSH / không có màn hình X11 $DISPLAY)!\033[0m")
        print("\033[93m👉 Hãy xem phim trực tiếp trên màn hình Terminal bằng lệnh CLI:\033[0m")
        print("\033[96m   ./ani.sh \"Tên Phim\"\033[0m\n")
        sys.exit(1)

    try:
        app = CyberStreamerGUI()
        app.mainloop()
    except Exception as e:
        print(f"\033[91m❌ Lỗi khởi chạy GUI Tkinter: {e}\033[0m")
        print("\033[93m👉 Vui lòng sử dụng chế độ xem trực tiếp trên Terminal CLI:\033[0m")
        print("\033[96m   ./ani.sh \"Tên Phim\"\033[0m\n")
