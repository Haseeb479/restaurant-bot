using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using System.Drawing;

namespace FoodioPosApp
{
    static class Program
    {
        private const string AppTitle = "Foodio Restaurant POS";
        private const string TargetUrl = "https://restaurant-bot-production-ba02.up.railway.app/login";

        [STAThread]
        static void Main()
        {
            try
            {
                // Find system Edge binary
                string edgePath = @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe";
                if (!File.Exists(edgePath))
                {
                    edgePath = @"C:\Program Files\Microsoft\Edge\Application\msedge.exe";
                }

                if (File.Exists(edgePath))
                {
                    // Isolated dedicated desktop user data directory for POS session
                    string appData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
                    string userProfile = Path.Combine(appData, "FoodioPOS_DesktopData");

                    ProcessStartInfo psi = new ProcessStartInfo();
                    psi.FileName = edgePath;
                    psi.Arguments = string.Format(
                        "--app=\"{0}\" --user-data-dir=\"{1}\" --window-size=1440,900 --no-first-run --no-default-browser-check",
                        TargetUrl,
                        userProfile
                    );
                    psi.UseShellExecute = true;

                    Process.Start(psi);
                }
                else
                {
                    // Fallback to default browser
                    Process.Start(new ProcessStartInfo(TargetUrl) { UseShellExecute = true });
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Could not launch POS Desktop: " + ex.Message, AppTitle, MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
