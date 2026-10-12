using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using System.Runtime.InteropServices;

namespace FoodioInstaller
{
    static class Program
    {
        private const string AppTitle = "Foodio POS Setup";
        private const string TargetUrl = "https://restaurant-bot-production-ba02.up.railway.app/login";

        [STAThread]
        static void Main()
        {
            try
            {
                // 1. Destination directories
                string programFiles = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
                string installDir = Path.Combine(programFiles, "FoodioPOS");
                if (!Directory.Exists(installDir))
                {
                    Directory.CreateDirectory(installDir);
                }

                // 2. Write runner app
                string targetExe = Path.Combine(installDir, "FoodioPOS.exe");
                string selfPath = System.Reflection.Assembly.GetExecutingAssembly().Location;
                
                // Copy or write binary
                if (File.Exists(selfPath) && !string.Equals(selfPath, targetExe, StringComparison.OrdinalIgnoreCase))
                {
                    File.Copy(selfPath, targetExe, true);
                }

                // 3. Create Windows Desktop Shortcut
                string desktopPath = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                string startMenuPath = Environment.GetFolderPath(Environment.SpecialFolder.StartMenu);

                CreateShortcut(
                    Path.Combine(desktopPath, "Foodio POS.lnk"),
                    targetExe,
                    "Foodio Restaurant POS & Kitchen Command Center"
                );

                // Start Menu shortcut
                string startPrograms = Path.Combine(startMenuPath, "Programs");
                if (Directory.Exists(startPrograms))
                {
                    CreateShortcut(
                        Path.Combine(startPrograms, "Foodio POS.lnk"),
                        targetExe,
                        "Foodio Restaurant POS & Kitchen Command Center"
                    );
                }

                // 4. Launch app immediately
                LaunchApp(TargetUrl);

                MessageBox.Show(
                    "Foodio Restaurant POS has been successfully installed!\n\nA shortcut 'Foodio POS' has been placed on your Desktop.\nYou can now launch it directly anytime.",
                    AppTitle,
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information
                );
            }
            catch (Exception ex)
            {
                MessageBox.Show("Installation failed: " + ex.Message, AppTitle, MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        static void LaunchApp(string url)
        {
            string edgePath = @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe";
            if (!File.Exists(edgePath))
            {
                edgePath = @"C:\Program Files\Microsoft\Edge\Application\msedge.exe";
            }

            string appData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
            string userProfile = Path.Combine(appData, "FoodioPOS_DesktopData");

            ProcessStartInfo psi = new ProcessStartInfo();
            if (File.Exists(edgePath))
            {
                psi.FileName = edgePath;
                psi.Arguments = string.Format(
                    "--app=\"{0}\" --user-data-dir=\"{1}\" --window-size=1440,900 --no-first-run --no-default-browser-check",
                    url,
                    userProfile
                );
            }
            else
            {
                psi.FileName = url;
            }
            psi.UseShellExecute = true;
            Process.Start(psi);
        }

        static void CreateShortcut(string shortcutPath, string targetPath, string description)
        {
            try
            {
                Type shellType = Type.GetTypeFromProgID("WScript.Shell");
                if (shellType == null) return;
                dynamic shell = Activator.CreateInstance(shellType);
                dynamic shortcut = shell.CreateShortcut(shortcutPath);
                shortcut.TargetPath = targetPath;
                shortcut.WorkingDirectory = Path.GetDirectoryName(targetPath);
                shortcut.Description = description;
                shortcut.Save();
            }
            catch { }
        }
    }
}
