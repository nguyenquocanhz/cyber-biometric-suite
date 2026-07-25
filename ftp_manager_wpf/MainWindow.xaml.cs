using System;
using System.Collections.Generic;
using System.IO;
using System.Threading;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using Microsoft.Win32;
using FtpManager.Services;
using MessageBox = System.Windows.MessageBox;
using SaveFileDialog = Microsoft.Win32.SaveFileDialog;

namespace FtpManager
{
    public class LocalFileItem
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

    public partial class MainWindow : Window
    {
        private readonly FtpService _ftpService = new FtpService();
        private List<FtpProfile> _profiles = new List<FtpProfile>();
        private string _currentLocalPath = "";
        private CancellationTokenSource? _transferCts;

        public MainWindow()
        {
            InitializeComponent();
            
            // Listen to FTP progress
            _ftpService.ProgressChanged += OnFtpProgressChanged;
            
            // Initialize path to User's Documents
            SetLocalPath(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments));
            
            // Load saved profiles
            LoadSavedProfiles();
            
            // Enable default values from FTP prompt
            HostTextBox.Text = "160.191.243.92";
            PortTextBox.Text = "21";
            UserTextBox.Text = "nqatech_shopkcvip.com";
            PassPasswordBox.Password = "anhanh123@@";
        }

        private void LoadSavedProfiles()
        {
            _profiles = ProfileManager.LoadProfiles();
            ProfileComboBox.ItemsSource = null;
            ProfileComboBox.ItemsSource = _profiles;
            ProfileComboBox.DisplayMemberPath = "Name";
            
            if (_profiles.Count > 0)
            {
                ProfileComboBox.SelectedIndex = 0;
            }
        }

        private void OnFtpProgressChanged(double percent, string statusText)
        {
            Dispatcher.Invoke(() =>
            {
                TaskProgressBar.Value = percent;
                StatusTextBlock.Text = statusText;
            });
        }

        private void SetLocalPath(string path)
        {
            if (Directory.Exists(path))
            {
                _currentLocalPath = path;
                LocalPathTextBox.Text = path;
                LoadLocalDirectory();
            }
        }

        private void LoadLocalDirectory()
        {
            try
            {
                var items = new List<LocalFileItem>();
                var dirInfo = new DirectoryInfo(_currentLocalPath);

                // Add directories
                foreach (var dir in dirInfo.GetDirectories())
                {
                    items.Add(new LocalFileItem
                    {
                        Name = dir.Name,
                        FullPath = dir.FullName,
                        IsDirectory = true,
                        Size = 0,
                        Modified = dir.LastWriteTime
                    });
                }

                // Add files
                foreach (var file in dirInfo.GetFiles())
                {
                    items.Add(new LocalFileItem
                    {
                        Name = file.Name,
                        FullPath = file.FullName,
                        IsDirectory = false,
                        Size = file.Length,
                        Modified = file.LastWriteTime
                    });
                }

                // Sort directories first
                items.Sort((x, y) =>
                {
                    if (x.IsDirectory != y.IsDirectory)
                        return y.IsDirectory.CompareTo(x.IsDirectory);
                    return string.Compare(x.Name, y.Name, StringComparison.OrdinalIgnoreCase);
                });

                LocalListView.ItemsSource = items;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Lỗi tải danh mục cục bộ: {ex.Message}", "Lỗi", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private async Task LoadRemoteDirectory(string remotePath)
        {
            try
            {
                var items = await _ftpService.ListDirectoryAsync(remotePath);
                RemoteListView.ItemsSource = items;
                RemotePathTextBox.Text = _ftpService.CurrentRemotePath;
                RemoteUpBtn.IsEnabled = _ftpService.CurrentRemotePath != "/";
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Lỗi tải danh mục FTP: {ex.Message}", "Lỗi", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private async void ConnectBtn_Click(object sender, RoutedEventArgs e)
        {
            if (_ftpService.IsConnected)
            {
                // Disconnect
                ConnectBtn.IsEnabled = false;
                await _ftpService.DisconnectAsync();
                ConnectBtn.Content = "KẾT NỐI";
                ConnectBtn.IsEnabled = true;
                RemoteListView.ItemsSource = null;
                RemoteUpBtn.IsEnabled = false;
                RefreshRemoteBtn.IsEnabled = false;
                return;
            }

            // Connect
            string host = HostTextBox.Text.Trim();
            if (!int.TryParse(PortTextBox.Text.Trim(), out int port)) port = 21;
            string user = UserTextBox.Text.Trim();
            string pass = PassPasswordBox.Password;

            if (string.IsNullOrEmpty(host) || string.IsNullOrEmpty(user))
            {
                MessageBox.Show("Vui lòng điền đầy đủ Host và Tài khoản!", "Thiếu thông tin", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            ConnectBtn.IsEnabled = false;
            try
            {
                await _ftpService.ConnectAsync(host, port, user, pass);
                ConnectBtn.Content = "NGẮT KẾT NỐI";
                RefreshRemoteBtn.IsEnabled = true;
                await LoadRemoteDirectory("/");
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Kết nối thất bại: {ex.Message}", "Lỗi kết nối", MessageBoxButton.OK, MessageBoxImage.Error);
                StatusTextBlock.Text = "Kết nối thất bại.";
            }
            finally
            {
                ConnectBtn.IsEnabled = true;
            }
        }

        private void SaveProfileBtn_Click(object sender, RoutedEventArgs e)
        {
            string host = HostTextBox.Text.Trim();
            string user = UserTextBox.Text.Trim();
            
            if (string.IsNullOrEmpty(host) || string.IsNullOrEmpty(user))
            {
                MessageBox.Show("Vui lòng nhập Host và User trước khi lưu profile!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            string profileName = $"{user}@{host}";
            
            // Check if profile exists, update or add new
            var existing = _profiles.Find(p => p.Name == profileName);
            if (existing != null)
            {
                existing.Host = host;
                existing.Port = int.TryParse(PortTextBox.Text, out int p) ? p : 21;
                existing.Username = user;
                existing.Password = PassPasswordBox.Password;
            }
            else
            {
                _profiles.Add(new FtpProfile
                {
                    Name = profileName,
                    Host = host,
                    Port = int.TryParse(PortTextBox.Text, out int p) ? p : 21,
                    Username = user,
                    Password = PassPasswordBox.Password
                });
            }

            ProfileManager.SaveProfiles(_profiles);
            LoadSavedProfiles();
            MessageBox.Show("Đã lưu cấu hình tài khoản thành công!", "Thành công", MessageBoxButton.OK, MessageBoxImage.Information);
        }

        private void ProfileComboBox_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (ProfileComboBox.SelectedItem is FtpProfile profile)
            {
                HostTextBox.Text = profile.Host;
                PortTextBox.Text = profile.Port.ToString();
                UserTextBox.Text = profile.Username;
                PassPasswordBox.Password = profile.Password;
            }
        }

        private void LocalListView_MouseDoubleClick(object sender, MouseButtonEventArgs e)
        {
            if (LocalListView.SelectedItem is LocalFileItem selectedItem && selectedItem.IsDirectory)
            {
                SetLocalPath(selectedItem.FullPath);
            }
        }

        private void LocalUpBtn_Click(object sender, RoutedEventArgs e)
        {
            var parent = Directory.GetParent(_currentLocalPath);
            if (parent != null)
            {
                SetLocalPath(parent.FullName);
            }
        }

        private void SelectLocalDirBtn_Click(object sender, RoutedEventArgs e)
        {
            using (var dialog = new System.Windows.Forms.FolderBrowserDialog())
            {
                dialog.InitialDirectory = _currentLocalPath;
                dialog.Description = "Chọn thư mục làm việc cục bộ";
                dialog.UseDescriptionForTitle = true;

                if (dialog.ShowDialog() == System.Windows.Forms.DialogResult.OK)
                {
                    SetLocalPath(dialog.SelectedPath);
                }
            }
        }

        private async void RemoteListView_MouseDoubleClick(object sender, MouseButtonEventArgs e)
        {
            if (RemoteListView.SelectedItem is FtpFileItem selectedItem && selectedItem.IsDirectory)
            {
                await LoadRemoteDirectory(selectedItem.FullPath);
            }
        }

        private async void RemoteUpBtn_Click(object sender, RoutedEventArgs e)
        {
            if (_ftpService.CurrentRemotePath == "/") return;
            
            // Get parent path
            string current = _ftpService.CurrentRemotePath.TrimEnd('/');
            int lastSlash = current.LastIndexOf('/');
            string parent = lastSlash <= 0 ? "/" : current.Substring(0, lastSlash);
            
            await LoadRemoteDirectory(parent);
        }

        private async void RefreshRemoteBtn_Click(object sender, RoutedEventArgs e)
        {
            await LoadRemoteDirectory(_ftpService.CurrentRemotePath);
        }

        private async void UploadBtn_Click(object sender, RoutedEventArgs e)
        {
            if (!_ftpService.IsConnected)
            {
                MessageBox.Show("Vui lòng kết nối FTP trước!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            if (LocalListView.SelectedItem is not LocalFileItem selectedItem)
            {
                MessageBox.Show("Vui lòng chọn 1 file cục bộ để tải lên!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            if (selectedItem.IsDirectory)
            {
                MessageBox.Show("Tính năng này chỉ hỗ trợ tải lên từng File. Kéo thả thư mục chưa hỗ trợ ở bản cơ bản.", "Thông tin", MessageBoxButton.OK, MessageBoxImage.Information);
                return;
            }

            UploadBtn.IsEnabled = false;
            DownloadBtn.IsEnabled = false;
            BackupBtn.IsEnabled = false;
            
            _transferCts = new CancellationTokenSource();
            
            try
            {
                string remoteFilePath = (_ftpService.CurrentRemotePath == "/" ? "/" : _ftpService.CurrentRemotePath + "/") + selectedItem.Name;
                await _ftpService.UploadFileAsync(selectedItem.FullPath, remoteFilePath, _transferCts.Token);
                await LoadRemoteDirectory(_ftpService.CurrentRemotePath);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Tải lên thất bại: {ex.Message}", "Lỗi tải lên", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally
            {
                UploadBtn.IsEnabled = true;
                DownloadBtn.IsEnabled = true;
                BackupBtn.IsEnabled = true;
                _transferCts = null;
            }
        }

        private async void DownloadBtn_Click(object sender, RoutedEventArgs e)
        {
            if (!_ftpService.IsConnected)
            {
                MessageBox.Show("Vui lòng kết nối FTP trước!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            if (RemoteListView.SelectedItem is not FtpFileItem selectedItem)
            {
                MessageBox.Show("Vui lòng chọn 1 file từ FTP để tải xuống!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            if (selectedItem.IsDirectory)
            {
                MessageBox.Show("Để tải xuống cả thư mục, vui lòng dùng tính năng 'Backup ZIP' để tải và nén nhanh gọn!", "Gợi ý", MessageBoxButton.OK, MessageBoxImage.Information);
                return;
            }

            UploadBtn.IsEnabled = false;
            DownloadBtn.IsEnabled = false;
            BackupBtn.IsEnabled = false;
            
            _transferCts = new CancellationTokenSource();
            
            try
            {
                string localFilePath = Path.Combine(_currentLocalPath, selectedItem.Name);
                await _ftpService.DownloadFileAsync(selectedItem.FullPath, localFilePath, _transferCts.Token);
                LoadLocalDirectory();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Tải xuống thất bại: {ex.Message}", "Lỗi tải xuống", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally
            {
                UploadBtn.IsEnabled = true;
                DownloadBtn.IsEnabled = true;
                BackupBtn.IsEnabled = true;
                _transferCts = null;
            }
        }

        private async void BackupBtn_Click(object sender, RoutedEventArgs e)
        {
            if (!_ftpService.IsConnected)
            {
                MessageBox.Show("Vui lòng kết nối FTP trước!", "Thông báo", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            string remoteDirToBackup = _ftpService.CurrentRemotePath;
            
            // If they selected a folder on the remote list, backup that folder specifically
            if (RemoteListView.SelectedItem is FtpFileItem selectedItem && selectedItem.IsDirectory)
            {
                remoteDirToBackup = selectedItem.FullPath;
            }

            var dialog = new SaveFileDialog
            {
                Filter = "ZIP Files (*.zip)|*.zip",
                FileName = $"backup_code_{DateTime.Now:yyyyMMdd_HHmmss}.zip",
                Title = "Chọn nơi lưu file sao lưu ZIP"
            };

            if (dialog.ShowDialog() != true) return;

            UploadBtn.IsEnabled = false;
            DownloadBtn.IsEnabled = false;
            BackupBtn.IsEnabled = false;
            
            _transferCts = new CancellationTokenSource();
            
            try
            {
                await _ftpService.BackupDirectoryAsync(remoteDirToBackup, dialog.FileName, _transferCts.Token);
                LoadLocalDirectory(); // Refresh local dir to show new ZIP backup if saved here
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Sao lưu thất bại: {ex.Message}", "Lỗi sao lưu", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally
            {
                UploadBtn.IsEnabled = true;
                DownloadBtn.IsEnabled = true;
                BackupBtn.IsEnabled = true;
                _transferCts = null;
            }
        }

        protected override async void OnClosed(EventArgs e)
        {
            base.OnClosed(e);
            await _ftpService.DisconnectAsync();
        }
    }
}