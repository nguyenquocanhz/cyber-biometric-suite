#!/usr/bin/env ruby
# frozen_string_literal: true
# ==============================================================================
# ANI-CLI RUBY - TÍCH HỢP SERVER KKPHIM (VIETSUB) & GOGOANIME
# Sử dụng 100% Thư viện chuẩn (Standard Libraries): net/http, json, uri, optparse
# ==============================================================================

require 'net/http'
require 'json'
require 'uri'
require 'optparse'

# Mã màu Terminal ANSI
CYAN    = "\e[96m"
MAGENTA = "\e[95m"
GREEN   = "\e[92m"
YELLOW  = "\e[93m"
RED     = "\e[91m"
BOLD    = "\e[1m"
RESET   = "\e[0m"

class AniCliRuby
  attr_accessor :player, :terminal_mode, :episode, :server, :url_only

  def initialize
    @player = 'mpv'
    @terminal_mode = false
    @episode = nil
    @server = 'kkphim' # 'kkphim' (Vietsub) hoặc 'gogoanime' (Engsub)
    @url_only = false
    @user_agent = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0'
  end

  def print_banner
    return if @url_only

    puts "#{CYAN}#{BOLD}"
    puts '  💎 CYBER MEDIA STREAMER CLI (KKPHIM VIETSUB + GOGOANIME) 💎'
    puts '  -----------------------------------------------------------'
    puts "  [ LINUX TERMINAL STREAMER // RUBY v#{RUBY_VERSION} // SERVER: KKPHIM ]#{RESET}\n\n"
  end

  def http_get(url_str)
    uri = URI.parse(url_str)
    http = Net::HTTP.new(uri.host, uri.port)
    http.use_ssl = (uri.scheme == 'https')
    http.open_timeout = 8
    http.read_timeout = 8

    request = Net::HTTP::Get.new(uri.request_uri, {
      'User-Agent' => @user_agent,
      'Accept' => 'application/json'
    })
    response = http.request(request)
    response.code == '200' ? response.body : nil
  rescue StandardError
    nil
  end

  # Tìm kiếm phim từ Server KKPhim API
  def search_kkphim(query)
    puts "#{YELLOW}🔍 Đang tìm kiếm trên Server KKPhim (Vietsub) cho: '#{query}'...#{RESET}" unless @url_only
    api_url = "https://phimapi.com/v1/api/tim-kiem?keyword=#{URI.encode_www_form_component(query)}"
    body = http_get(api_url)

    return [] unless body

    begin
      data = JSON.parse(body)
      items = data.dig('data', 'items') || []
      items.map do |item|
        {
          'id' => item['slug'],
          'title' => "#{item['name']} (#{item['origin_name']} - #{item['year']})",
          'source' => 'kkphim'
        }
      end
    rescue JSON::ParserError
      []
    end
  end

  # Tìm kiếm phim từ Gogoanime Fallback
  def search_gogoanime(query)
    puts "#{YELLOW}🔍 Đang tìm kiếm trên Server GogoAnime (Engsub) cho: '#{query}'...#{RESET}" unless @url_only
    api_url = "https://consumet-api-clone.vercel.app/anime/gogoanime/#{URI.encode_www_form_component(query)}"
    body = http_get(api_url)

    return [] unless body

    begin
      data = JSON.parse(body)
      results = data['results'] || []
      results.map do |item|
        {
          'id' => item['id'],
          'title' => "#{item['title']} (EngSub)",
          'source' => 'gogoanime'
        }
      end
    rescue JSON::ParserError
      []
    end
  end

  # Lấy danh sách tập phim từ KKPhim API
  def get_kkphim_episodes(slug)
    api_url = "https://phimapi.com/phim/#{slug}"
    body = http_get(api_url)

    return nil unless body

    begin
      data = JSON.parse(body)
      episodes_data = data['episodes'] || []
      return nil if episodes_data.empty?

      server_data = episodes_data.first['server_data'] || []
      server_data.map do |ep|
        {
          'name' => ep['name'],
          'link_m3u8' => ep['link_m3u8'],
          'slug' => ep['slug']
        }
      end
    rescue JSON::ParserError
      nil
    end
  end

  # Lấy danh sách tập từ Gogoanime
  def get_gogoanime_episodes(media_id)
    api_url = "https://consumet-api-clone.vercel.app/anime/gogoanime/info/#{media_id}"
    body = http_get(api_url)

    return [] unless body

    begin
      data = JSON.parse(body)
      episodes = data['episodes'] || []
      episodes.map do |ep|
        {
          'name' => "Tập #{ep['number']}",
          'id' => ep['id']
        }
      end
    rescue JSON::ParserError
      []
    end
  end

  # Lấy luồng phát m3u8 từ Gogoanime
  def get_gogoanime_stream(episode_id)
    api_url = "https://consumet-api-clone.vercel.app/anime/gogoanime/watch/#{episode_id}"
    body = http_get(api_url)

    return nil unless body

    begin
      data = JSON.parse(body)
      sources = data['sources'] || []
      sources.each do |s|
        return s['url'] if %w[default 1080p 720p].include?(s['quality'])
      end
      sources.first['url']
    rescue JSON::ParserError
      nil
    end
  end

  def play_with_mpv(stream_url, title)
    if @url_only
      puts "\n#{GREEN}#{BOLD}🔗 LINK STREAM (COPY DÁN VÀO VLC / MPV TRÊN LAPTOP CỦA BẠN):#{RESET}"
      puts "#{CYAN}#{stream_url}#{RESET}\n\n"
      return
    end

    puts "\n#{GREEN}#{BOLD}▶ Đang phát luồng phim bằng MPV...#{RESET}"
    puts "#{MAGENTA}m3u8 Stream: #{stream_url}#{RESET}\n"

    cmd = [@player]

    if @terminal_mode
      # Render trực tiếp trong Terminal TTY
      cmd += ['--vo=tixel', '--ao=pulse,alsa,sdl,auto', '--really-quiet', '--no-ytdl']
    else
      cmd += [
        "--force-media-title=#{title}",
        '--geometry=1280x720',
        '--ao=pulse,alsa,sdl,auto',
        '--no-ytdl',
        "--user-agent=#{@user_agent}",
        '--referrer=https://phimapi.com/'
      ]
    end

    cmd << stream_url

    begin
      exec(*cmd)
    rescue Errno::ENOENT
      puts "\n#{RED}❌ Lỗi: MPV chưa được cài đặt! Hướng dẫn cài: sudo apt install mpv#{RESET}\n"
    end
  end
end

cli = AniCliRuby.new

OptionParser.new do |opts|
  opts.banner = 'Sử dụng: ruby ani_cli.rb [options] [tên_phim]'

  opts.on('-e', '--episode EPISODE', Integer, 'Chỉ định số tập cần xem') do |ep|
    cli.episode = ep
  end

  opts.on('-s', '--server SERVER', String, 'Chọn Server: kkphim (Vietsub) hoặc gogoanime (Engsub)') do |srv|
    cli.server = srv.downcase
  end

  opts.on('-u', '--url-only', 'Chỉ lấy Link Stream .m3u8 để dán vào VLC/MPV trên Laptop') do
    cli.url_only = true
  end

  opts.on('-t', '--terminal', 'Phát video trực tiếp trong màn hình Terminal TTY') do
    cli.terminal_mode = true
  end

  opts.on('-h', '--help', 'Hiển thị hướng dẫn') do
    puts opts
    exit
  end
end.parse!

cli.print_banner

query = ARGV.join(' ').strip
if query.empty?
  print "#{CYAN}Nhập tên Phim / Anime cần xem: #{RESET}"
  query = STDIN.gets.to_s.strip
end

if query.empty?
  puts "#{RED}Chưa nhập tên phim. Đã thoát.#{RESET}"
  exit 1
end

# Tìm kiếm từ KKPhim hoặc Gogoanime
results = if cli.server == 'gogoanime'
            cli.search_gogoanime(query)
          else
            res = cli.search_kkphim(query)
            res.empty? ? cli.search_gogoanime(query) : res
          end

if results.empty?
  puts "#{RED}Không tìm thấy kết quả nào cho '#{query}'.#{RESET}"
  exit 1
end

puts "\n#{GREEN}#{BOLD}Danh sách kết quả tìm kiếm (Server #{cli.server.upcase}):#{RESET}" unless cli.url_only
results[0..9].each_with_index do |item, idx|
  puts " #{CYAN}[#{idx + 1}]#{RESET} #{item['title']}"
end

print "\n#{YELLOW}Chọn số thứ tự phim [1-#{[10, results.length].min}]: #{RESET}" unless cli.url_only
choice = STDIN.gets.to_s.strip
selected_idx = choice.to_i.positive? ? choice.to_i - 1 : 0
selected = results[selected_idx] || results.first

puts "\n#{GREEN}Đang lấy danh sách tập cho '#{selected['title']}'...#{RESET}" unless cli.url_only

if selected['source'] == 'kkphim'
  episodes = cli.get_kkphim_episodes(selected['id'])
  if episodes && !episodes.empty?
    puts "#{CYAN}Tìm thấy tổng cộng #{episodes.length} tập Vietsub.#{RESET}" unless cli.url_only
    ep_num = cli.episode
    unless ep_num
      print "#{YELLOW}Chọn số Tập phim [1-#{episodes.length}]: #{RESET}" unless cli.url_only
      ep_input = STDIN.gets.to_s.strip
      ep_num = ep_input.to_i.positive? ? ep_input.to_i : 1
    end

    selected_ep = episodes[[0, [ep_num - 1, episodes.length - 1].min].max]
    cli.play_with_mpv(selected_ep['link_m3u8'], "#{selected['title']} - #{selected_ep['name']}")
  else
    puts "#{RED}Không tìm thấy tập phim trên KKPhim Server.#{RESET}"
  end
else
  episodes = cli.get_gogoanime_episodes(selected['id'])
  puts "#{CYAN}Tìm thấy tổng cộng #{episodes.length} tập Engsub.#{RESET}" unless cli.url_only
  ep_num = cli.episode
  unless ep_num
    print "#{YELLOW}Chọn số Tập phim [1-#{episodes.length}]: #{RESET}" unless cli.url_only
    ep_input = STDIN.gets.to_s.strip
    ep_num = ep_input.to_i.positive? ? ep_input.to_i : 1
  end

  selected_ep = episodes[[0, [ep_num - 1, episodes.length - 1].min].max]
  stream_url = cli.get_gogoanime_stream(selected_ep['id'])
  if stream_url
    cli.play_with_mpv(stream_url, "#{selected['title']} - Tập #{ep_num}")
  else
    puts "#{RED}Không lấy được luồng stream.#{RESET}"
  end
end
