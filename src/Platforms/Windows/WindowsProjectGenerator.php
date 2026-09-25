<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Windows;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Exceptions\BuildException;
use Illuminate\Support\Facades\File;

class WindowsProjectGenerator
{
    public function generate(BuildContextInterface $context): bool
    {
        $projectPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'windows-project';
        
        // Clean previous project
        if (is_dir($projectPath)) {
            File::deleteDirectory($projectPath);
        }

        mkdir($projectPath, 0755, true);

        // 1. Generate C# project files
        $this->generateProjectFiles($context, $projectPath);

        // 2. Copy Laravel application
        $laravelDir = $projectPath . DIRECTORY_SEPARATOR . 'laravel';
        mkdir($laravelDir, 0755, true);
        
        $this->copyLaravelApp($context, $laravelDir);

        return true;
    }

    protected function generateProjectFiles(BuildContextInterface $context, string $projectPath): void
    {
        $appName = preg_replace('/[^a-zA-Z0-9]/', '', $context->appName()) ?: 'LaraNativeApp';
        
        // csproj
        $csproj = <<<XML
<Project Sdk="Microsoft.NET.Sdk">
  <PropertyGroup>
    <OutputType>WinExe</OutputType>
    <TargetFramework>net8.0-windows</TargetFramework>
    <Nullable>enable</Nullable>
    <UseWindowsForms>true</UseWindowsForms>
    <ImplicitUsings>enable</ImplicitUsings>
    <AssemblyName>LaraNativeApp</AssemblyName>
  </PropertyGroup>

  <ItemGroup>
    <PackageReference Include="Microsoft.Web.WebView2" Version="1.0.2592.51" />
  </ItemGroup>
</Project>
XML;
        file_put_contents($projectPath . DIRECTORY_SEPARATOR . 'LaraNativeApp.csproj', $csproj);

        // Program.cs
        $port = $context->config('runtime.port', 8080);
        $width = $context->config('native.window.width', 1280);
        $height = $context->config('native.window.height', 800);
        $dbFilename = $context->config('database.filename', 'database.sqlite');
        $displayAppName = addslashes($context->appName());

        $programCs = <<<CS
using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using Microsoft.Web.WebView2.Core;
using Microsoft.Web.WebView2.WinForms;

namespace LaraNativeApp
{
    static class Program
    {
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new MainForm());
        }
    }

    public class MainForm : Form
    {
        private WebView2? webView;
        private Process? phpProcess;
        private string appName = "{$displayAppName}";
        private int port = {$port};
        
        public MainForm()
        {
            this.Text = appName;
            this.Width = {$width};
            this.Height = {$height};
            this.StartPosition = FormStartPosition.CenterScreen;
            
            StartPhpServer();
            InitializeWebView();
            
            this.FormClosing += (s, e) => {
                if (phpProcess != null && !phpProcess.HasExited) {
                    phpProcess.Kill();
                }
            };
        }

        private void StartPhpServer()
        {
            // For Single-File applications, BaseDirectory points to a temp folder.
            // MainModule.FileName points to the actual .exe location.
            string? exePath = Process.GetCurrentProcess().MainModule?.FileName;
            string baseDir = exePath != null ? Path.GetDirectoryName(exePath) ?? AppDomain.CurrentDomain.BaseDirectory : AppDomain.CurrentDomain.BaseDirectory;
            
            string laravelDir = Path.Combine(baseDir, "laravel");
            string phpExe = Path.Combine(baseDir, "php", "php.exe");
            
            // Fallback to system PHP for development if bundled PHP doesn't exist
            if (!File.Exists(phpExe)) {
                phpExe = "php";
            }
            
            ProcessStartInfo startInfo = new ProcessStartInfo();
            startInfo.FileName = phpExe;
            startInfo.Arguments = $"artisan serve --host=127.0.0.1 --port={port}";
            startInfo.WorkingDirectory = laravelDir;
            startInfo.UseShellExecute = false;
            startInfo.CreateNoWindow = true;
            
            // Setup environment
            startInfo.EnvironmentVariables["APP_ENV"] = "production";
            startInfo.EnvironmentVariables["DB_CONNECTION"] = "sqlite";
            startInfo.EnvironmentVariables["DB_DATABASE"] = Path.Combine(laravelDir, "database", "{$dbFilename}");

            try {
                phpProcess = Process.Start(startInfo);
            } catch (Exception ex) {
                MessageBox.Show("Failed to start PHP server: " + ex.Message + "\\n\\nMake sure PHP is in your PATH or bundled.", "Error");
            }
        }

        private async void InitializeWebView()
        {
            webView = new WebView2();
            webView.Dock = DockStyle.Fill;
            this.Controls.Add(webView);
            
            string safeAppName = appName.Replace(" ", "");
            var userDataFolder = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), safeAppName, "WebView2");
            var environment = await CoreWebView2Environment.CreateAsync(null, userDataFolder);
            await webView.EnsureCoreWebView2Async(environment);
            
            webView.CoreWebView2.Navigate($"http://127.0.0.1:{port}");
        }
    }
}
CS;
        file_put_contents($projectPath . DIRECTORY_SEPARATOR . 'Program.cs', $programCs);
    }

    protected function copyLaravelApp(BuildContextInterface $context, string $dest): void
    {
        $sourceApp = $context->buildPath() . DIRECTORY_SEPARATOR . 'app';

        if (is_dir($sourceApp)) {
            $this->copyDirectory($sourceApp, $dest);
        }
    }

    protected function copyDirectory(string $source, string $target): void
    {
        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $dest = $target . DIRECTORY_SEPARATOR . $items->getSubPathname();

            if ($item->isDir()) {
                if (! is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }
            } else {
                copy($item->getRealPath(), $dest);
            }
        }
    }
}
