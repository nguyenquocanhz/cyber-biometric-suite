using System;
using System.Collections.Generic;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;

namespace FtpManager.Services
{
    public class FtpProfile
    {
        public string Id { get; set; } = Guid.NewGuid().ToString();
        public string Name { get; set; } = "";
        public string Host { get; set; } = "";
        public int Port { get; set; } = 21;
        public string Username { get; set; } = "";
        public string EncryptedPassword { get; set; } = "";
        public string RemotePath { get; set; } = "/";

        [System.Text.Json.Serialization.JsonIgnore]
        public string Password
        {
            get => Decrypt(EncryptedPassword);
            set => EncryptedPassword = Encrypt(value);
        }

        // A simple encryption fallback helper for local config protection
        private static readonly byte[] Entropy = { 0x14, 0x09, 0x22, 0x11, 0x88, 0x12, 0x44, 0x55 };

        private static string Encrypt(string clearText)
        {
            if (string.IsNullOrEmpty(clearText)) return "";
            try
            {
                byte[] clearBytes = Encoding.UTF8.GetBytes(clearText);
                byte[] encryptedBytes = ProtectedData.Protect(clearBytes, Entropy, DataProtectionScope.CurrentUser);
                return Convert.ToBase64String(encryptedBytes);
            }
            catch
            {
                // Fallback if DPAPI is not available
                return Convert.ToBase64String(Encoding.UTF8.GetBytes(clearText));
            }
        }

        private static string Decrypt(string encryptedText)
        {
            if (string.IsNullOrEmpty(encryptedText)) return "";
            try
            {
                byte[] encryptedBytes = Convert.FromBase64String(encryptedText);
                byte[] clearBytes = ProtectedData.Unprotect(encryptedBytes, Entropy, DataProtectionScope.CurrentUser);
                return Encoding.UTF8.GetString(clearBytes);
            }
            catch
            {
                // Fallback
                try
                {
                    return Encoding.UTF8.GetString(Convert.FromBase64String(encryptedText));
                }
                catch
                {
                    return "";
                }
            }
        }
    }

    public static class ProfileManager
    {
        private static readonly string FilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "profiles.json");

        public static List<FtpProfile> LoadProfiles()
        {
            try
            {
                if (!File.Exists(FilePath)) return new List<FtpProfile>();
                string json = File.ReadAllText(FilePath);
                return JsonSerializer.Deserialize<List<FtpProfile>>(json) ?? new List<FtpProfile>();
            }
            catch
            {
                return new List<FtpProfile>();
            }
        }

        public static void SaveProfiles(List<FtpProfile> profiles)
        {
            try
            {
                string json = JsonSerializer.Serialize(profiles, new JsonSerializerOptions { WriteIndented = true });
                File.WriteAllText(FilePath, json);
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine("Error saving profiles: " + ex.Message);
            }
        }
    }
}
