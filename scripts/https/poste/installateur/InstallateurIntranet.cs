// Installateur graphique de l'intranet NÉRÉ Mining pour un poste Windows.
// Compilé par Construire-Installateur.ps1 avec le compilateur C# de .NET
// Framework 4 (C# 5 : pas d'interpolation de chaînes ni d'opérateur ?.).
//
// Étapes : approuver le certificat HTTPS de l'intranet, créer les raccourcis
// (bureau et menu Démarrer, fenêtre d'application Edge), configurer Firefox,
// vérifier le DNS et tester la connexion.
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Reflection;
using System.Security.Cryptography.X509Certificates;
using System.Text;
using System.Windows.Forms;
using Microsoft.Win32;

namespace NereIntranet
{
    internal static class Config
    {
        public const string Host = "OUA-NM-INTRANET.neremining.com";
        public const string Url = "https://OUA-NM-INTRANET.neremining.com/";
        public const string ServerIp = "192.168.10.206";
        public const string AppName = "Intranet NÉRÉ Mining";
        public const string Thumbprint = "C044B43D23054999374BAF109CBB1678AFC21EFF";
        public const string OldThumbprint = "DDFE6E8545A4CA68510CB68D248E6D5679611831";
        public const string CertificateBase64 =
            "MIIDmDCCAoCgAwIBAgIQXohc5EN40L5OuEYe3H0BKTANBgkqhkiG9w0BAQsFADBCMREwDwYDVQQL" +
            "DAhJbnRyYW5ldDEUMBIGA1UECgwLTkVSRSBNaW5pbmcxFzAVBgNVBAMMDjE5Mi4xNjguMTAuMjA2" +
            "MB4XDTI2MTAyNzExMjgyM1oXDTI4MTAyNjExMzgyMlowQjERMA8GA1UECwwISW50cmFuZXQxFDAS" +
            "BgNVBAoMC05FUkUgTWluaW5nMRcwFQYDVQQDDA4xOTIuMTY4LjEwLjIwNjCCASIwDQYJKoZIhvcN" +
            "AQEBBQADggEPADCCAQoCggEBALcv/1ccIgEYSEGQncsBNrHCxP5reAfwfeBsXnwRSFjX1zKRmjJI" +
            "PRRGGojvfVb45BfSuJxTydh8ECPYIUR74VnG9flKZZcU5cPhTsK4Br888dQ9X5BaAYShEVerGyC7" +
            "o27/z3Vu0XMllET5Zj/v3TMXt+7/xZy65mWqvK8Ls/GQUOkQcLIznboUvGJ1L5IFm0v6ewU7SJat" +
            "kkrze6cKINsm5EoAzgkjOXJ2BOnveiMNUuoRUTh6cZA+arpwnPsHdCxrsdFJ8WSdzluaGddBZ06B" +
            "eOl+gnLsAW8mX8uf0JC7/opWZOsJjOF6DTJr3V6s3vA1FPkH6l7yyrSTYi2AiL8CAwEAAaOBiTCB" +
            "hjAOBgNVHQ8BAf8EBAMCBaAwQAYDVR0RBDkwN4cEwKgKzoIeT1VBLU5NLUlOVFJBTkVULm5lcmVt" +
            "aW5pbmcuY29tgg9PVUEtTk0tSU5UUkFORVQwEwYDVR0lBAwwCgYIKwYBBQUHAwEwHQYDVR0OBBYE" +
            "FJ32rhJfCTdn2H6+sMoqSlZww9ykMA0GCSqGSIb3DQEBCwUAA4IBAQA054TCpeYObVj/DeyEV8en" +
            "II9ymGe5t5wm8U+aFTMWoKLll9Gvo/TCaUbTExS+podj7wxYwNr/gXT3tZZ7PFWoEoAn/JRfgLEL" +
            "09brlLKIWNgaSwPVxCUd+Wp94LYvLq+8gatmZmT6fDHr8oN5toZyNAUEV80fOx3tKk4V13rvggJw" +
            "wuJi3a5UOQ+msqoUCfD16Ux+u5cYXPwZOw8ZVtuqXZqJMKxR7f6fTXC0y9Yo8Acd3BBnncWNt7rs" +
            "EnycrQg0x8aFS+CC9+95zmyzJF/ESRta8wct5pTwdZkhCt0UOy6iKN1TLh3d/mQJJCTCXflZcNsa" +
            "Rhc9byBpDcr/WBcn";

        public static readonly Color Brand = Color.FromArgb(200, 53, 48);
        public static readonly Color BrandDark = Color.FromArgb(61, 15, 14);
    }

    internal enum StepState { Pending, Running, Ok, Warning, Failed, Skipped }

    internal sealed class StepResult
    {
        public int Index;
        public StepState State;
        public string Detail;
    }

    internal sealed class InstallStep
    {
        public string Title;
        public Func<StepResult> Run;
        public Label StatusLabel;
        public Label TitleLabel;
        public Label DetailLabel;
    }

    internal static class Installer
    {
        public static StepResult InstallCertificate()
        {
            var certificate = new X509Certificate2(Convert.FromBase64String(Config.CertificateBase64));
            var store = new X509Store(StoreName.Root, StoreLocation.LocalMachine);
            store.Open(OpenFlags.ReadWrite);
            try
            {
                foreach (X509Certificate2 old in store.Certificates.Find(X509FindType.FindByThumbprint, Config.OldThumbprint, false))
                {
                    store.Remove(old);
                }
                store.Add(certificate);
                bool present = store.Certificates.Find(X509FindType.FindByThumbprint, Config.Thumbprint, false).Count > 0;

                return present
                    ? Result(StepState.Ok, "Approuvé par Windows (Edge, Chrome), valable jusqu'au 26/10/2028.")
                    : Result(StepState.Failed, "Le certificat est introuvable après l'installation.");
            }
            finally
            {
                store.Close();
            }
        }

        public static StepResult CreateShortcuts()
        {
            string folder = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "NERE Intranet");
            Directory.CreateDirectory(folder);
            string icon = Path.Combine(folder, "intranet.ico");
            using (Stream resource = Assembly.GetExecutingAssembly().GetManifestResourceStream("intranet.ico"))
            using (FileStream file = File.Create(icon))
            {
                resource.CopyTo(file);
            }

            string browser = FindBrowser();
            string[] targets =
            {
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonDesktopDirectory), Config.AppName + ".lnk"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonPrograms), Config.AppName + ".lnk"),
            };

            Type shellType = Type.GetTypeFromProgID("WScript.Shell");
            object shell = Activator.CreateInstance(shellType);
            foreach (string target in targets)
            {
                object link = shellType.InvokeMember("CreateShortcut", BindingFlags.InvokeMethod, null, shell, new object[] { target });
                Type linkType = link.GetType();
                if (browser != null)
                {
                    linkType.InvokeMember("TargetPath", BindingFlags.SetProperty, null, link, new object[] { browser });
                    linkType.InvokeMember("Arguments", BindingFlags.SetProperty, null, link, new object[] { "--app=" + Config.Url });
                }
                else
                {
                    linkType.InvokeMember("TargetPath", BindingFlags.SetProperty, null, link, new object[] { Config.Url });
                }
                linkType.InvokeMember("IconLocation", BindingFlags.SetProperty, null, link, new object[] { icon + ",0" });
                linkType.InvokeMember("Description", BindingFlags.SetProperty, null, link, new object[] { Config.AppName });
                linkType.InvokeMember("Save", BindingFlags.InvokeMethod, null, link, null);
            }

            return browser != null
                ? Result(StepState.Ok, "Icône sur le bureau et dans le menu Démarrer, ouverture dans sa propre fenêtre.")
                : Result(StepState.Warning, "Edge et Chrome introuvables : les icônes ouvrent le navigateur par défaut.");
        }

        public static bool FirefoxInstalled()
        {
            string[] paths =
            {
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), @"Mozilla Firefox\firefox.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), @"Mozilla Firefox\firefox.exe"),
            };

            return paths.Any(File.Exists);
        }

        public static StepResult ConfigureFirefox()
        {
            if (!FirefoxInstalled())
            {
                return Result(StepState.Skipped, "Firefox n'est pas installé sur ce poste.");
            }

            using (RegistryKey key = Registry.LocalMachine.CreateSubKey(@"SOFTWARE\Policies\Mozilla\Firefox\Certificates"))
            {
                key.SetValue("ImportEnterpriseRoots", 1, RegistryValueKind.DWord);
            }

            return Result(StepState.Ok, "Firefox utilise désormais les certificats approuvés par Windows.");
        }

        public static StepResult CheckDns()
        {
            IPAddress[] addresses;
            try
            {
                addresses = Dns.GetHostAddresses(Config.Host);
            }
            catch (SocketException)
            {
                return Result(StepState.Failed, "Nom introuvable : ce poste n'utilise pas le DNS de l'entreprise ou n'est pas sur le réseau.");
            }

            if (addresses.Any(a => a.ToString() == Config.ServerIp))
            {
                return Result(StepState.Ok, Config.Host + " pointe bien vers " + Config.ServerIp + ".");
            }

            return Result(StepState.Failed,
                "Le nom pointe vers " + string.Join(", ", addresses.Select(a => a.ToString())) +
                ", un site externe. Le poste doit utiliser le DNS de l'entreprise (10.226.70.31) : coupez le VPN ou corrigez la carte réseau.");
        }

        public static StepResult TestConnection()
        {
            ServicePointManager.SecurityProtocol = (SecurityProtocolType)3072; // TLS 1.2
            var request = (HttpWebRequest)WebRequest.Create(Config.Url + "connexion");
            request.Timeout = 15000;
            request.AllowAutoRedirect = false;
            try
            {
                using (var response = (HttpWebResponse)request.GetResponse())
                {
                    return response.StatusCode == HttpStatusCode.OK
                        ? Result(StepState.Ok, "Connexion sécurisée à l'intranet réussie.")
                        : Result(StepState.Warning, "Réponse inattendue du serveur (code " + (int)response.StatusCode + ").");
                }
            }
            catch (WebException exception)
            {
                if (exception.Status == WebExceptionStatus.TrustFailure || exception.Status == WebExceptionStatus.SecureChannelFailure)
                {
                    return Result(StepState.Failed, "Le certificat présenté par le serveur n'est pas approuvé.");
                }

                return Result(StepState.Failed, "Intranet injoignable : " + exception.Message);
            }
        }

        private static string FindBrowser()
        {
            string[] candidates =
            {
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), @"Microsoft\Edge\Application\msedge.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), @"Microsoft\Edge\Application\msedge.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), @"Google\Chrome\Application\chrome.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), @"Google\Chrome\Application\chrome.exe"),
            };

            return candidates.FirstOrDefault(File.Exists);
        }

        private static StepResult Result(StepState state, string detail)
        {
            return new StepResult { State = state, Detail = detail };
        }
    }

    internal sealed class MainForm : Form
    {
        private readonly Panel welcomePage = new Panel();
        private readonly Panel progressPage = new Panel();
        private readonly CheckBox shortcutsBox = new CheckBox();
        private readonly CheckBox firefoxBox = new CheckBox();
        private readonly ProgressBar progressBar = new ProgressBar();
        private readonly Label summaryLabel = new Label();
        private readonly Button primaryButton = new Button();
        private readonly Button secondaryButton = new Button();
        private readonly List<InstallStep> steps = new List<InstallStep>();
        private readonly BackgroundWorker worker = new BackgroundWorker();
        private bool finished;

        public MainForm()
        {
            Text = "Installation - " + Config.AppName;
            Font = new Font("Segoe UI", 9.5f);
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            StartPosition = FormStartPosition.CenterScreen;
            ClientSize = new Size(620, 560);
            BackColor = Color.White;
            AutoScaleMode = AutoScaleMode.Dpi;
            Icon = LoadIcon();

            Controls.Add(BuildButtonBar());
            Controls.Add(BuildHeader());

            BuildWelcomePage();
            BuildProgressPage();
            Controls.Add(welcomePage);
            Controls.Add(progressPage);
            welcomePage.BringToFront();
            progressPage.Visible = false;

            worker.WorkerReportsProgress = true;
            worker.DoWork += DoInstall;
            worker.ProgressChanged += ShowStepResult;
            worker.RunWorkerCompleted += InstallCompleted;
        }

        private static Icon LoadIcon()
        {
            using (Stream resource = Assembly.GetExecutingAssembly().GetManifestResourceStream("intranet.ico"))
            {
                return resource == null ? SystemIcons.Application : new Icon(resource);
            }
        }

        private Control BuildHeader()
        {
            var header = new Panel { Dock = DockStyle.Top, Height = 96, BackColor = Config.BrandDark };
            header.Paint += (sender, e) =>
            {
                using (var brush = new System.Drawing.Drawing2D.LinearGradientBrush(header.ClientRectangle, Config.BrandDark, Config.Brand, 0f))
                {
                    e.Graphics.FillRectangle(brush, header.ClientRectangle);
                }
            };
            var logo = new PictureBox
            {
                Image = Image.FromStream(Assembly.GetExecutingAssembly().GetManifestResourceStream("logo.png")),
                SizeMode = PictureBoxSizeMode.Zoom,
                Size = new Size(48, 48),
                Location = new Point(24, 24),
                BackColor = Color.Transparent,
            };
            var title = new Label
            {
                Text = Config.AppName,
                ForeColor = Color.White,
                BackColor = Color.Transparent,
                Font = new Font("Segoe UI Semibold", 16f),
                AutoSize = true,
                Location = new Point(84, 20),
            };
            var subtitle = new Label
            {
                Text = "Installation sur ce poste",
                ForeColor = Color.FromArgb(253, 224, 140),
                BackColor = Color.Transparent,
                AutoSize = true,
                Location = new Point(86, 56),
            };
            header.Controls.Add(logo);
            header.Controls.Add(title);
            header.Controls.Add(subtitle);

            return header;
        }

        private Control BuildButtonBar()
        {
            var bar = new Panel { Dock = DockStyle.Bottom, Size = new Size(ClientSize.Width, 64), BackColor = Color.FromArgb(246, 246, 247) };
            primaryButton.Size = new Size(170, 36);
            primaryButton.Location = new Point(bar.Width - 24 - 170, 14);
            primaryButton.Anchor = AnchorStyles.Right | AnchorStyles.Top;
            primaryButton.FlatStyle = FlatStyle.Flat;
            primaryButton.FlatAppearance.BorderSize = 0;
            primaryButton.BackColor = Config.Brand;
            primaryButton.ForeColor = Color.White;
            primaryButton.Font = new Font("Segoe UI Semibold", 10f);
            primaryButton.Text = "Installer";
            primaryButton.Click += PrimaryClicked;

            secondaryButton.Size = new Size(120, 36);
            secondaryButton.Location = new Point(primaryButton.Left - 12 - 120, 14);
            secondaryButton.Anchor = AnchorStyles.Right | AnchorStyles.Top;
            secondaryButton.FlatStyle = FlatStyle.Flat;
            secondaryButton.FlatAppearance.BorderColor = Color.FromArgb(200, 200, 205);
            secondaryButton.Text = "Annuler";
            secondaryButton.Click += (sender, e) => Close();

            bar.Controls.Add(primaryButton);
            bar.Controls.Add(secondaryButton);
            AcceptButton = primaryButton;

            return bar;
        }

        private void BuildWelcomePage()
        {
            welcomePage.Dock = DockStyle.Fill;
            welcomePage.Padding = new Padding(28, 20, 28, 10);

            var intro = new Label
            {
                Text = "Cet assistant prépare ce poste pour l'intranet NÉRÉ Mining :\n" +
                       "le site s'ouvrira sans alerte de sécurité, depuis une icône dédiée.",
                AutoSize = false,
                Size = new Size(560, 48),
                Location = new Point(28, 20),
            };
            var required = new CheckBox
            {
                Text = "Approuver le certificat de sécurité de l'intranet (obligatoire)",
                Checked = true,
                Enabled = false,
                AutoSize = true,
                Location = new Point(32, 84),
            };
            shortcutsBox.Text = "Créer l'icône « " + Config.AppName + " » sur le bureau et dans le menu Démarrer";
            shortcutsBox.Checked = true;
            shortcutsBox.AutoSize = true;
            shortcutsBox.Location = new Point(32, 116);

            bool firefox = Installer.FirefoxInstalled();
            firefoxBox.Text = firefox ? "Configurer Firefox" : "Configurer Firefox (non installé sur ce poste)";
            firefoxBox.Checked = firefox;
            firefoxBox.Enabled = firefox;
            firefoxBox.AutoSize = true;
            firefoxBox.Location = new Point(32, 148);

            var note = new Label
            {
                Text = "L'assistant vérifie aussi que le poste trouve le serveur sur le réseau\n" +
                       "de l'entreprise, puis teste la connexion. Rien n'est installé d'autre.\n\n" +
                       "Adresse : " + Config.Url,
                ForeColor = Color.FromArgb(90, 90, 98),
                AutoSize = false,
                Size = new Size(560, 90),
                Location = new Point(28, 196),
            };

            welcomePage.Controls.Add(intro);
            welcomePage.Controls.Add(required);
            welcomePage.Controls.Add(shortcutsBox);
            welcomePage.Controls.Add(firefoxBox);
            welcomePage.Controls.Add(note);
        }

        private void BuildProgressPage()
        {
            progressPage.Dock = DockStyle.Fill;

            AddStep("Certificat de sécurité", Installer.InstallCertificate);
            AddStep("Icônes de l'intranet", () => shortcutsBox.Checked ? Installer.CreateShortcuts() : Skipped());
            AddStep("Firefox", () => firefoxBox.Checked ? Installer.ConfigureFirefox() : Skipped());
            AddStep("Réseau (DNS de l'entreprise)", Installer.CheckDns);
            AddStep("Connexion à l'intranet", Installer.TestConnection);

            int top = 20;
            foreach (InstallStep step in steps)
            {
                step.StatusLabel = new Label
                {
                    Text = "•",
                    Font = new Font("Segoe UI Semibold", 12f),
                    ForeColor = Color.FromArgb(160, 160, 168),
                    Size = new Size(28, 26),
                    Location = new Point(28, top),
                    TextAlign = ContentAlignment.MiddleCenter,
                };
                step.TitleLabel = new Label
                {
                    Text = step.Title,
                    Font = new Font("Segoe UI Semibold", 10f),
                    AutoSize = true,
                    Location = new Point(60, top + 2),
                };
                step.DetailLabel = new Label
                {
                    Text = "En attente",
                    ForeColor = Color.FromArgb(110, 110, 118),
                    AutoSize = false,
                    Size = new Size(530, 34),
                    Location = new Point(60, top + 24),
                };
                progressPage.Controls.Add(step.StatusLabel);
                progressPage.Controls.Add(step.TitleLabel);
                progressPage.Controls.Add(step.DetailLabel);
                top += 60;
            }

            progressBar.Location = new Point(28, top + 2);
            progressBar.Size = new Size(564, 8);
            progressBar.Maximum = steps.Count;
            summaryLabel.Location = new Point(28, top + 18);
            summaryLabel.Size = new Size(564, 40);
            summaryLabel.Font = new Font("Segoe UI Semibold", 10f);
            progressPage.Controls.Add(progressBar);
            progressPage.Controls.Add(summaryLabel);
        }

        private void AddStep(string title, Func<StepResult> run)
        {
            steps.Add(new InstallStep { Title = title, Run = run });
        }

        private static StepResult Skipped()
        {
            return new StepResult { State = StepState.Skipped, Detail = "Non demandé." };
        }

        private void PrimaryClicked(object sender, EventArgs e)
        {
            if (finished)
            {
                Process.Start(new ProcessStartInfo(Config.Url) { UseShellExecute = true });
                Close();
                return;
            }

            welcomePage.Visible = false;
            progressPage.Visible = true;
            progressPage.BringToFront();
            primaryButton.Enabled = false;
            secondaryButton.Enabled = false;
            primaryButton.Text = "Installation...";
            worker.RunWorkerAsync();
        }

        private void DoInstall(object sender, DoWorkEventArgs e)
        {
            for (int i = 0; i < steps.Count; i++)
            {
                worker.ReportProgress(0, new StepResult { Index = i, State = StepState.Running, Detail = "En cours..." });
                StepResult result;
                try
                {
                    result = steps[i].Run();
                }
                catch (Exception exception)
                {
                    result = new StepResult { State = StepState.Failed, Detail = exception.Message };
                }
                result.Index = i;
                worker.ReportProgress(0, result);
            }
        }

        private void ShowStepResult(object sender, ProgressChangedEventArgs e)
        {
            Apply((StepResult)e.UserState);
        }

        internal void Apply(StepResult result)
        {
            InstallStep step = steps[result.Index];
            step.DetailLabel.Text = result.Detail;
            switch (result.State)
            {
                case StepState.Running:
                    step.StatusLabel.Text = "…";
                    step.StatusLabel.ForeColor = Config.Brand;
                    break;
                case StepState.Ok:
                    step.StatusLabel.Text = "✓";
                    step.StatusLabel.ForeColor = Color.FromArgb(22, 128, 61);
                    break;
                case StepState.Warning:
                    step.StatusLabel.Text = "!";
                    step.StatusLabel.ForeColor = Color.FromArgb(180, 110, 0);
                    break;
                case StepState.Failed:
                    step.StatusLabel.Text = "✗";
                    step.StatusLabel.ForeColor = Color.FromArgb(185, 28, 28);
                    step.DetailLabel.ForeColor = Color.FromArgb(185, 28, 28);
                    break;
                case StepState.Skipped:
                    step.StatusLabel.Text = "–";
                    step.StatusLabel.ForeColor = Color.FromArgb(160, 160, 168);
                    break;
            }
            if (result.State != StepState.Running)
            {
                progressBar.Value = Math.Min(progressBar.Maximum, result.Index + 1);
                step.TitleLabel.Tag = result.State;
            }
        }

        private void InstallCompleted(object sender, RunWorkerCompletedEventArgs e)
        {
            bool failed = steps.Any(s => Equals(s.TitleLabel.Tag, StepState.Failed));
            Finish(failed);
        }

        internal void Finish(bool failed)
        {
            finished = true;
            summaryLabel.Text = failed
                ? "Installation terminée avec des erreurs : voir les étapes en rouge."
                : "Installation terminée. Fermez puis rouvrez votre navigateur.";
            summaryLabel.ForeColor = failed ? Color.FromArgb(185, 28, 28) : Color.FromArgb(22, 128, 61);
            primaryButton.Text = "Ouvrir l'intranet";
            primaryButton.Enabled = true;
            secondaryButton.Text = "Fermer";
            secondaryButton.Enabled = true;
        }

        // Aperçu pour la documentation et les tests : capture la fenêtre sans rien installer.
        internal void SavePreview(string path, bool progress)
        {
            if (progress)
            {
                welcomePage.Visible = false;
                progressPage.Visible = true;
                progressPage.BringToFront();
                Apply(new StepResult { Index = 0, State = StepState.Ok, Detail = "Approuvé par Windows (Edge, Chrome), valable jusqu'au 26/10/2028." });
                Apply(new StepResult { Index = 1, State = StepState.Ok, Detail = "Icône sur le bureau et dans le menu Démarrer, ouverture dans sa propre fenêtre." });
                Apply(new StepResult { Index = 2, State = StepState.Skipped, Detail = "Firefox n'est pas installé sur ce poste." });
                Apply(Installer.CheckDns().WithIndex(3));
                Apply(Installer.TestConnection().WithIndex(4));
                Finish(steps.Any(s => Equals(s.TitleLabel.Tag, StepState.Failed)));
            }
            using (var bitmap = new Bitmap(Width, Height))
            {
                DrawToBitmap(bitmap, new Rectangle(0, 0, Width, Height));
                bitmap.Save(path, System.Drawing.Imaging.ImageFormat.Png);
            }
        }
    }

    internal static class StepResultExtensions
    {
        public static StepResult WithIndex(this StepResult result, int index)
        {
            result.Index = index;
            return result;
        }
    }

    internal static class Program
    {
        [STAThread]
        private static void Main(string[] args)
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            var form = new MainForm();
            if (args.Length == 3 && args[0] == "/apercu")
            {
                form.CreateControl();
                form.Show();
                Application.DoEvents();
                form.SavePreview(args[1], args[2] == "progression");
                return;
            }

            Application.Run(form);
        }
    }
}
