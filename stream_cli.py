#!/usr/bin/env python3
import os
import sys
import argparse
import subprocess
import urllib.parse
import json
import requests

# Cấu hình mã hóa UTF-8 cho Windows Terminal
if sys.platform.startswith('win'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

# Terminal ANSI Styling
CYAN = "\033[96m"
MAGENTA = "\033[95m"
GREEN = "\033[92m"
YELLOW = "\033[93m"
RED = "\033[91m"
BOLD = "\033[1m"
RESET = "\033[0m"

class CyberStreamCLI:
    """
    Công cụ Ani-CLI & Streamer Phim Vietsub (KKPhim) & Anime (GogoAnime) chuyên dụng cho Linux CLI.
    """
    def __init__(self, player="mpv", terminal_mode=False, server="kkphim"):
        self.player = player
        self.terminal_mode = terminal_mode
        self.server = server
        self.headers = {
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0",
            "Accept": "application/json"
        }

    def print_banner(self):
        print(f"{CYAN}{BOLD}")
        print("   ███████╗████████╗██████╗ ███████╗█████╗ ███╗   ███╗    ██████╗██╗     ██╗")
        print("   ██╔════╝╚══██╔══╝██╔══██╗██╔════╝██╔══██╗████╗ ████║   ██╔════╝██║     ██║")
        print("   ███████╗   ██║   ██████╔╝█████╗  ███████║██╔████╔██║   ██║     ██║     ██║")
        print("   ╚════██║   ██║   ██╔══██╗██╔══╝  ██╔══██║██║╚██╔╝██║   ██║     ██║     ██║")
        print("   ███████║   ██║   ██║  ██║███████╗██║  ██║██║ ╚═╝ ██║   ╚██████╗███████╗██║")
        print("   ╚══════╝   ╚═╝   ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝    ╚═════╝╚══════╝╚═╝")
        print(f"             [ LINUX STREAMER // SERVER: KKPHIM VIETSUB & GOGOANIME ]{RESET}\n")

    def search_kkphim(self, query):
        print(f"{YELLOW}🔍 Đang tìm kiếm trên Server KKPhim (Vietsub) cho từ khóa: '{query}'...{RESET}")
        url = f"https://phimapi.com/v1/api/tim-kiem?keyword={urllib.parse.quote(query)}"
        try:
            resp = requests.get(url, headers=self.headers, timeout=8)
            if resp.status_code == 200:
                data = resp.json()
                items = data.get("data", {}).get("items", [])
                results = []
                for item in items:
                    results.append({
                        "id": item.get("slug"),
                        "title": f"{item.get('name')} ({item.get('origin_name')} - {item.get('year')})",
                        "source": "kkphim"
                    })
                return results
        except Exception:
            pass
        return []

    def search_gogoanime(self, query):
        print(f"{YELLOW}🔍 Đang tìm kiếm trên Server GogoAnime (Engsub) cho: '{query}'...{RESET}")
        url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/{urllib.parse.quote(query)}"
        try:
            resp = requests.get(url, headers=self.headers, timeout=8)
            if resp.status_code == 200:
                data = resp.json()
                results = data.get("results", [])
                res = []
                for r in results:
                    res.append({
                        "id": r.get("id"),
                        "title": f"{r.get('title')} (EngSub)",
                        "source": "gogoanime"
                    })
                return res
        except Exception:
            pass
        return []

    def get_kkphim_episodes(self, slug):
        url = f"https://phimapi.com/phim/{slug}"
        try:
            resp = requests.get(url, headers=self.headers, timeout=8)
            if resp.status_code == 200:
                data = resp.json()
                episodes = data.get("episodes", [])
                if episodes:
                    server_data = episodes[0].get("server_data", [])
                    return server_data
        except Exception:
            pass
        return []

    def get_gogoanime_episodes(self, media_id):
        url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/info/{media_id}"
        try:
            resp = requests.get(url, headers=self.headers, timeout=8)
            if resp.status_code == 200:
                data = resp.json()
                return data.get("episodes", [])
        except Exception:
            pass
        return []

    def get_gogoanime_stream(self, episode_id):
        url = f"https://consumet-api-clone.vercel.app/anime/gogoanime/watch/{episode_id}"
        try:
            resp = requests.get(url, headers=self.headers, timeout=8)
            if resp.status_code == 200:
                data = resp.json()
                sources = data.get("sources", [])
                for s in sources:
                    if s.get("quality") in ["default", "1080p", "720p"]:
                        return s.get("url")
                if sources:
                    return sources[0].get("url")
        except Exception:
            pass
        return None

    def play_stream(self, stream_url, title="Stream"):
        print(f"\n{GREEN}{BOLD}▶ Đang khởi chạy MPV phát m3u8 stream...{RESET}")
        print(f"{MAGENTA}Stream URL: {stream_url}{RESET}\n")

        cmd = [self.player]

        if self.terminal_mode and self.player == "mpv":
            cmd.extend(["--vo=tixel", "--really-quiet", "--no-ytdl"])
        elif self.player == "mpv":
            cmd.extend([
                f"--force-media-title={title}",
                "--geometry=1280x720",
                "--no-ytdl",
                f"--user-agent={self.headers['User-Agent']}",
                "--referrer=https://phimapi.com/"
            ])

        cmd.append(stream_url)

        try:
            subprocess.run(cmd)
        except FileNotFoundError:
            print(f"\n{RED}❌ Lỗi: Trình phát '{self.player}' chưa được cài đặt! Hãy cài mpv: sudo apt install mpv{RESET}\n")

def main():
    parser = argparse.ArgumentParser(description="Cyber Stream CLI - Xem Phim Vietsub (KKPhim) & Anime (GogoAnime)")
    parser.add_argument("query", nargs="?", type=str, help="Từ khóa tên phim hoặc anime cần xem")
    parser.add_argument("-s", "--server", type=str, default="kkphim", choices=["kkphim", "gogoanime"], help="Chọn Server (kkphim hoặc gogoanime)")
    parser.add_argument("-p", "--player", type=str, default="mpv", help="Trình phát media (mặc định: mpv)")
    parser.add_argument("-t", "--terminal", action="store_true", help="Hiển thị video trực tiếp trong Terminal TTY")
    parser.add_argument("-e", "--episode", type=int, help="Chỉ định số tập cần xem")

    args = parser.parse_args()

    cli = CyberStreamCLI(player=args.player, terminal_mode=args.terminal, server=args.server)
    cli.print_banner()

    query = args.query
    if not query:
        query = input(f"{CYAN}Nhập tên Phim / Anime cần xem: {RESET}").strip()

    if not query:
        print(f"{RED}Chưa nhập tên phim. Đã thoát.{RESET}")
        sys.exit(1)

    if args.server == "gogoanime":
        results = cli.search_gogoanime(query)
    else:
        results = cli.search_kkphim(query)
        if not results:
            results = cli.search_gogoanime(query)

    if not results:
        print(f"{RED}Không tìm thấy kết quả nào cho '{query}'.{RESET}")
        sys.exit(1)

    print(f"\n{GREEN}{BOLD}Danh sách kết quả tìm kiếm (Server {cli.server.upper()}):{RESET}")
    for idx, item in enumerate(results[:10], 1):
        print(f" {CYAN}[{idx}]{RESET} {item.get('title', 'Unknown')}")

    choice = input(f"\n{YELLOW}Chọn số thứ tự phim [1-{min(10, len(results))}]: {RESET}").strip()
    try:
        selected_idx = int(choice) - 1
        selected = results[selected_idx]
    except Exception:
        selected = results[0]

    media_id = selected.get("id")
    print(f"\n{GREEN}Đang lấy danh sách tập cho '{selected.get('title')}'...{RESET}")

    if selected.get("source") == "kkphim":
        episodes = cli.get_kkphim_episodes(media_id)
        if episodes:
            print(f"{CYAN}Tìm thấy tổng cộng {len(episodes)} tập Vietsub.{RESET}")
            ep_num = args.episode
            if not ep_num:
                ep_input = input(f"{YELLOW}Chọn số Tập phim [1-{len(episodes)}]: {RESET}").strip()
                try:
                    ep_num = int(ep_input)
                except Exception:
                    ep_num = 1

            selected_ep = episodes[min(max(0, ep_num - 1), len(episodes) - 1)]
            cli.play_stream(selected_ep.get("link_m3u8"), title=f"{selected.get('title')} - {selected_ep.get('name')}")
        else:
            print(f"{RED}Không tìm thấy tập phim.{RESET}")
    else:
        episodes = cli.get_gogoanime_episodes(media_id)
        print(f"{CYAN}Tìm thấy tổng cộng {len(episodes)} tập Engsub.{RESET}")
        ep_num = args.episode
        if not ep_num:
            ep_input = input(f"{YELLOW}Chọn số Tập phim [1-{len(episodes)}]: {RESET}").strip()
            try:
                ep_num = int(ep_input)
            except Exception:
                ep_num = 1

        selected_ep = episodes[min(max(0, ep_num - 1), len(episodes) - 1)]
        stream_url = cli.get_gogoanime_stream(selected_ep.get("id"))
        if stream_url:
            cli.play_stream(stream_url, title=f"{selected.get('title')} - Tập {ep_num}")
        else:
            print(f"{RED}Không lấy được luồng stream.{RESET}")

if __name__ == "__main__":
    main()
