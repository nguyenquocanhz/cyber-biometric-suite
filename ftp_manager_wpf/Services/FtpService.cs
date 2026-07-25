using System;
using System.Collections.Generic;
using System.IO;
using System.IO.Compression;
using System.Threading;
using System.Threading.Tasks;
using FluentFTP;

namespace FtpManager.Services
{
    public class FtpFileItem
    {
        public string Name { get; set; } = "";
        public string FullPath { get; set; } = "";
        public bool IsDirectory { get; set; }
        public long Size { get; set; }
        public DateTime Modified { get; set; }
        
        public string Icon => IsDirectory ? "📁" : "📄";
        public string IconColor => IsDirectory ? "#f59e0b" : "#94a3b8";
        public string ModifiedDisplay => Modified.ToString("dd/MM/yyyy HH:mm");
        public string SizeDisplay => IsDirectory ? "--" : FormatSize(Size);

        private static string FormatSize(long bytes)
        {
            string[] suffixes = { "B", "KB", "MB", "GB", "TB" };
            int counter = 0;
            double number = bytes;
            while (Math.Round(number / 1024) >= 1)
            {
                number /= 1024;
                counter++;
            }
            return $"{number:n1} {suffixes[counter]}";
        }
    }

    public class FtpService : IDisposable
    {
        private AsyncFtpClient? _client;
        
        public bool IsConnected => _client?.IsConnected ?? false;
        public string CurrentRemotePath { get; private set; } = "/";

        public event Action<double, string>? ProgressChanged;

        public async Task ConnectAsync(string host, int port, string user, string pass)
        {
            ReportProgress(0, "Đang kết nối...");
            
            _client = new AsyncFtpClient(host, user, pass, port);
            
            // Auto detect encryption if available
            _client.Config.EncryptionMode = FtpEncryptionMode.Auto;
            _client.Config.ValidateAnyCertificate = true;
            _client.Config.ConnectTimeout = 10000; // 10 seconds

            await _client.Connect();
            CurrentRemotePath = "/";
            ReportProgress(100, "Đã kết nối thành công!");
        }

        public async Task DisconnectAsync()
        {
            if (_client != null)
            {
                await _client.Disconnect();
                _client.Dispose();
                _client = null;
            }
            ReportProgress(0, "Đã ngắt kết nối.");
        }

        public async Task<List<FtpFileItem>> ListDirectoryAsync(string remotePath)
        {
            if (_client == null || !_client.IsConnected)
                throw new InvalidOperationException("Chưa kết nối FTP!");

            ReportProgress(30, "Đang tải danh sách thư mục...");
            var items = new List<FtpFileItem>();
            
            // Normalise path
            if (string.IsNullOrEmpty(remotePath)) remotePath = "/";
            
            var listing = await _client.GetListing(remotePath);
            CurrentRemotePath = remotePath;

            foreach (var item in listing)
            {
                items.Add(new FtpFileItem
                {
                    Name = item.Name,
                    FullPath = item.FullName,
                    IsDirectory = item.Type == FtpObjectType.Directory,
                    Size = item.Size,
                    Modified = item.Modified
                });
            }

            // Sort directories first, then files
            items.Sort((x, y) =>
            {
                if (x.IsDirectory != y.IsDirectory)
                    return y.IsDirectory.CompareTo(x.IsDirectory);
                return string.Compare(x.Name, y.Name, StringComparison.OrdinalIgnoreCase);
            });

            ReportProgress(100, "Tải danh sách thư mục hoàn tất.");
            return items;
        }

        public async Task UploadFileAsync(string localPath, string remotePath, CancellationToken token = default)
        {
            if (_client == null || !_client.IsConnected)
                throw new InvalidOperationException("Chưa kết nối FTP!");

            var progress = new Progress<FtpProgress>(p =>
            {
                ReportProgress(p.Progress, $"Đang tải lên: {p.Progress:0.0}% ({FormatBytes(p.TransferredBytes)})");
            });

            ReportProgress(0, "Bắt đầu tải lên...");
            var result = await _client.UploadFile(localPath, remotePath, FtpRemoteExists.Overwrite, true, FtpVerify.None, progress, token);
            
            if (result != FtpStatus.Success)
            {
                throw new Exception("Lỗi khi tải file lên máy chủ FTP!");
            }
            ReportProgress(100, "Tải lên thành công!");
        }

        public async Task DownloadFileAsync(string remotePath, string localPath, CancellationToken token = default)
        {
            if (_client == null || !_client.IsConnected)
                throw new InvalidOperationException("Chưa kết nối FTP!");

            var progress = new Progress<FtpProgress>(p =>
            {
                ReportProgress(p.Progress, $"Đang tải xuống: {p.Progress:0.0}% ({FormatBytes(p.TransferredBytes)})");
            });

            ReportProgress(0, "Bắt đầu tải xuống...");
            var result = await _client.DownloadFile(localPath, remotePath, FtpLocalExists.Overwrite, FtpVerify.None, progress, token);
            
            if (result != FtpStatus.Success)
            {
                throw new Exception("Lỗi khi tải file xuống máy tính!");
            }
            ReportProgress(100, "Tải xuống thành công!");
        }

        public async Task BackupDirectoryAsync(string remoteDirPath, string localZipFilePath, CancellationToken token = default)
        {
            if (_client == null || !_client.IsConnected)
                throw new InvalidOperationException("Chưa kết nối FTP!");

            // Create temp folder for downloads
            string tempDir = Path.Combine(Path.GetTempPath(), "FtpBackup_" + Guid.NewGuid().ToString("N"));
            Directory.CreateDirectory(tempDir);

            try
            {
                ReportProgress(0, "Đang chuẩn bị tải toàn bộ thư mục về để nén...");
                
                // FluentFTP recursive directory download
                var progress = new Progress<FtpProgress>(p =>
                {
                    ReportProgress(p.Progress, $"Đang tải dữ liệu: {p.Progress:0.0}% (Đã chuyển {FormatBytes(p.TransferredBytes)})");
                });

                var results = await _client.DownloadDirectory(tempDir, remoteDirPath, FtpFolderSyncMode.Update, FtpLocalExists.Overwrite, FtpVerify.None, null, progress, token);
                
                ReportProgress(90, "Đang nén thư mục thành file ZIP...");
                
                // Compress download files
                if (File.Exists(localZipFilePath)) File.Delete(localZipFilePath);
                await Task.Run(() => ZipFile.CreateFromDirectory(tempDir, localZipFilePath, CompressionLevel.Optimal, false), token);

                ReportProgress(100, $"Sao lưu thành công! Đã lưu tại: {Path.GetFileName(localZipFilePath)}");
            }
            finally
            {
                // Clean up temp directory safely
                try
                {
                    if (Directory.Exists(tempDir))
                        Directory.Delete(tempDir, true);
                }
                catch { }
            }
        }

        private void ReportProgress(double percent, string statusText)
        {
            ProgressChanged?.Invoke(percent, statusText);
        }

        private static string FormatBytes(long bytes)
        {
            if (bytes >= 1024 * 1024 * 1024) return $"{(double)bytes / (1024 * 1024 * 1024):0.00} GB";
            if (bytes >= 1024 * 1024) return $"{(double)bytes / (1024 * 1024):0.00} MB";
            if (bytes >= 1024) return $"{(double)bytes / 1024:0.00} KB";
            return $"{bytes} B";
        }

        public void Dispose()
        {
            _client?.Dispose();
        }
    }
}
