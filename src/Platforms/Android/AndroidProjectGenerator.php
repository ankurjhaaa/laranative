<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Android;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Environment\Compatibility;

/**
 * Generates a real Android project structure with Gradle build files,
 * AndroidManifest, and a WebView-based MainActivity.
 */
class AndroidProjectGenerator
{
    /**
     * Generate the complete Android project.
     */
    public function generate(BuildContextInterface $context): bool
    {
        $projectPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'android-project';

        // Create project structure
        $this->createDirectoryStructure($projectPath, $context);

        // Generate build files
        $this->generateSettingsGradle($projectPath, $context);
        $this->generateRootBuildGradle($projectPath);
        $this->generateAppBuildGradle($projectPath, $context);
        $this->generateGradleProperties($projectPath);
        $this->generateGradleWrapper($projectPath);

        // Generate app source
        $this->generateAndroidManifest($projectPath, $context);
        $this->generateMainActivity($projectPath, $context);
        $this->generateLaraNativeApplication($projectPath, $context);
        $this->generatePhpServer($projectPath, $context);
        $this->generateStringsXml($projectPath, $context);
        $this->generateStylesXml($projectPath, $context);
        $this->generateNetworkSecurityConfig($projectPath);

        // Copy Laravel application to assets
        $this->copyLaravelApp($context, $projectPath);

        return true;
    }

    /**
     * Create the Android project directory structure.
     */
    protected function createDirectoryStructure(string $projectPath, BuildContextInterface $context): void
    {
        $packagePath = str_replace('.', DIRECTORY_SEPARATOR, $context->appId());

        $dirs = [
            $projectPath,
            $projectPath . '/app/src/main/java/' . $packagePath,
            $projectPath . '/app/src/main/res/layout',
            $projectPath . '/app/src/main/res/values',
            $projectPath . '/app/src/main/res/xml',
            $projectPath . '/app/src/main/res/drawable',
            $projectPath . '/app/src/main/res/mipmap-hdpi',
            $projectPath . '/app/src/main/res/mipmap-mdpi',
            $projectPath . '/app/src/main/res/mipmap-xhdpi',
            $projectPath . '/app/src/main/res/mipmap-xxhdpi',
            $projectPath . '/app/src/main/res/mipmap-xxxhdpi',
            $projectPath . '/app/src/main/assets/laravel',
            $projectPath . '/app/src/main/assets/php',
            $projectPath . '/gradle/wrapper',
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Generate settings.gradle.kts
     */
    protected function generateSettingsGradle(string $projectPath, BuildContextInterface $context): void
    {
        $appName = addslashes($context->appName());

        $content = <<<GRADLE
pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolution {
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "{$appName}"
include(":app")
GRADLE;

        file_put_contents($projectPath . '/settings.gradle.kts', $content);
    }

    /**
     * Generate root build.gradle.kts
     */
    protected function generateRootBuildGradle(string $projectPath): void
    {
        $content = <<<'GRADLE'
plugins {
    id("com.android.application") version "8.7.0" apply false
    id("org.jetbrains.kotlin.android") version "2.0.21" apply false
}
GRADLE;

        file_put_contents($projectPath . '/build.gradle.kts', $content);
    }

    /**
     * Generate app/build.gradle.kts
     */
    protected function generateAppBuildGradle(string $projectPath, BuildContextInterface $context): void
    {
        $appId = $context->appId();
        $versionName = $context->version();
        $versionCode = $context->config('version_code', 1);
        $minSdk = $context->config('platforms.android.min_sdk', Compatibility::MIN_ANDROID_API_LEVEL);
        $targetSdk = $context->config('platforms.android.target_sdk', Compatibility::TARGET_ANDROID_SDK);
        $compileSdk = $context->config('platforms.android.compile_sdk', Compatibility::MIN_ANDROID_COMPILE_SDK);

        $signingBlock = '';
        if ($context->shouldSign()) {
            $signing = $context->signingConfig();
            if ($signing) {
                $keystore = addslashes($signing['keystore'] ?? '');
                $keystorePassword = addslashes($signing['keystore_password'] ?? '');
                $keyAlias = addslashes($signing['key_alias'] ?? '');
                $keyPassword = addslashes($signing['key_password'] ?? '');

                $signingBlock = <<<GRADLE

    signingConfigs {
        create("release") {
            storeFile = file("{$keystore}")
            storePassword = "{$keystorePassword}"
            keyAlias = "{$keyAlias}"
            keyPassword = "{$keyPassword}"
        }
    }
GRADLE;
            }
        }

        $content = <<<GRADLE
plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "{$appId}"
    compileSdk = {$compileSdk}

    defaultConfig {
        applicationId = "{$appId}"
        minSdk = {$minSdk}
        targetSdk = {$targetSdk}
        versionCode = {$versionCode}
        versionName = "{$versionName}"
    }
{$signingBlock}
    buildTypes {
        release {
            isMinifyEnabled = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
        debug {
            isDebuggable = true
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        viewBinding = true
    }

    // Allow large asset files (Laravel app bundle)
    aaptOptions {
        noCompress("php", "sqlite", "env")
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("com.google.android.material:material:1.12.0")
    implementation("androidx.webkit:webkit:1.12.1")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.7")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.9.0")
}
GRADLE;

        file_put_contents($projectPath . '/app/build.gradle.kts', $content);

        // ProGuard rules
        $proguard = <<<'PROGUARD'
# LaraNative ProGuard Rules
-keep class android.webkit.** { *; }
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
PROGUARD;

        file_put_contents($projectPath . '/app/proguard-rules.pro', $proguard);
    }

    /**
     * Generate gradle.properties
     */
    protected function generateGradleProperties(string $projectPath): void
    {
        $content = <<<'PROPERTIES'
org.gradle.jvmargs=-Xmx2048m -Dfile.encoding=UTF-8
android.useAndroidX=true
kotlin.code.style=official
android.nonTransitiveRClass=true
PROPERTIES;

        file_put_contents($projectPath . '/gradle.properties', $content);
    }

    /**
     * Generate gradle wrapper properties (the wrapper JARs would be downloaded by Gradle).
     */
    protected function generateGradleWrapper(string $projectPath): void
    {
        $properties = <<<'PROPERTIES'
distributionBase=GRADLE_USER_HOME
distributionPath=wrapper/dists
distributionUrl=https\://services.gradle.org/distributions/gradle-8.9-bin.zip
networkTimeout=10000
validateDistributionUrl=true
zipStoreBase=GRADLE_USER_HOME
zipStorePath=wrapper/dists
PROPERTIES;

        file_put_contents($projectPath . '/gradle/wrapper/gradle-wrapper.properties', $properties);

        // Generate gradlew script
        $gradlew = <<<'BASH'
#!/bin/sh
# Gradle wrapper bootstrap script
# This downloads and runs Gradle automatically

APP_NAME="Gradle"
APP_BASE_NAME=$(basename "$0")
CLASSPATH=$APP_HOME/gradle/wrapper/gradle-wrapper.jar

# Determine JAVA_HOME
if [ -z "$JAVA_HOME" ]; then
    JAVACMD="java"
else
    JAVACMD="$JAVA_HOME/bin/java"
fi

exec "$JAVACMD" -classpath "$CLASSPATH" org.gradle.wrapper.GradleWrapperMain "$@"
BASH;

        file_put_contents($projectPath . '/gradlew', $gradlew);

        // Windows batch file
        $gradlewBat = <<<'BATCH'
@rem Gradle wrapper for Windows
@if "%DEBUG%"=="" @echo off
set DIRNAME=%~dp0
set CLASSPATH=%DIRNAME%\gradle\wrapper\gradle-wrapper.jar
"%JAVA_HOME%\bin\java.exe" -classpath "%CLASSPATH%" org.gradle.wrapper.GradleWrapperMain %*
BATCH;

        file_put_contents($projectPath . '/gradlew.bat', $gradlewBat);
    }

    /**
     * Generate AndroidManifest.xml
     */
    protected function generateAndroidManifest(string $projectPath, BuildContextInterface $context): void
    {
        $permissions = $context->config('platforms.android.permissions', ['INTERNET', 'ACCESS_NETWORK_STATE']);
        $permissionXml = '';

        foreach ($permissions as $perm) {
            $permissionXml .= "    <uses-permission android:name=\"android.permission.{$perm}\" />\n";
        }

        $content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

{$permissionXml}
    <application
        android:name=".LaraNativeApplication"
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:supportsRtl="true"
        android:theme="@style/Theme.LaraNative"
        android:usesCleartextTraffic="true"
        android:networkSecurityConfig="@xml/network_security_config">

        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:configChanges="orientation|screenSize|keyboardHidden"
            android:windowSoftInputMode="adjustResize">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>

</manifest>
XML;

        file_put_contents($projectPath . '/app/src/main/AndroidManifest.xml', $content);
    }

    /**
     * Generate the main activity with WebView.
     */
    protected function generateMainActivity(string $projectPath, BuildContextInterface $context): void
    {
        $packagePath = str_replace('.', '/', $context->appId());
        $packageName = $context->appId();

        $content = <<<KOTLIN
package {$packageName}

import android.annotation.SuppressLint
import android.os.Bundle
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.appcompat.app.AppCompatActivity

/**
 * Main activity hosting the WebView that displays the Laravel application.
 *
 * The Laravel app is served locally by an embedded PHP runtime. The WebView
 * connects to 127.0.0.1 — no data ever leaves the device unless the
 * application explicitly makes network requests.
 */
class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        webView = WebView(this).apply {
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.allowFileAccess = true
            settings.allowContentAccess = true
            settings.databaseEnabled = true
            settings.setSupportZoom(false)
            settings.useWideViewPort = true
            settings.loadWithOverviewMode = true

            webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                    val url = request?.url?.toString() ?: return false
                    // Keep local URLs in the WebView
                    if (url.startsWith("http://127.0.0.1") || url.startsWith("http://localhost")) {
                        return false
                    }
                    // Open external URLs in the system browser
                    return true
                }
            }

            webChromeClient = WebChromeClient()
        }

        setContentView(webView)

        // Load the locally-served Laravel application
        val app = application as LaraNativeApplication
        val serverUrl = app.getServerUrl()
        webView.loadUrl(serverUrl)
    }

    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            super.onBackPressed()
        }
    }

    override fun onDestroy() {
        webView.destroy()
        super.onDestroy()
    }
}
KOTLIN;

        $filePath = $projectPath . '/app/src/main/java/' . $packagePath . '/MainActivity.kt';
        file_put_contents($filePath, $content);
    }

    /**
     * Generate the Application class that manages the PHP server lifecycle.
     */
    protected function generateLaraNativeApplication(string $projectPath, BuildContextInterface $context): void
    {
        $packagePath = str_replace('.', '/', $context->appId());
        $packageName = $context->appId();
        $port = $context->config('runtime.port', 8080);

        $content = <<<KOTLIN
package {$packageName}

import android.app.Application
import android.util.Log
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Application class that manages the embedded PHP server lifecycle.
 *
 * On startup, it extracts the Laravel application from assets to the
 * internal storage directory and starts the local PHP server.
 */
class LaraNativeApplication : Application() {

    private val TAG = "LaraNative"
    private var phpServer: PhpServer? = null
    private val serverPort = {$port}

    override fun onCreate() {
        super.onCreate()

        CoroutineScope(Dispatchers.IO).launch {
            try {
                // Extract Laravel app from assets on first run or update
                val appDir = filesDir.resolve("laravel")
                if (!appDir.exists()) {
                    Log.i(TAG, "Extracting Laravel application...")
                    extractAssets("laravel", appDir)
                }

                // Ensure database directory exists
                val dbDir = appDir.resolve("database")
                if (!dbDir.exists()) {
                    dbDir.mkdirs()
                }

                // Start PHP server
                phpServer = PhpServer(applicationContext, appDir, serverPort)
                phpServer?.start()

                Log.i(TAG, "Laravel server started on port \$serverPort")
            } catch (e: Exception) {
                Log.e(TAG, "Failed to start LaraNative runtime", e)
            }
        }
    }

    fun getServerUrl(): String {
        return "http://127.0.0.1:\$serverPort"
    }

    override fun onTerminate() {
        phpServer?.stop()
        super.onTerminate()
    }

    /**
     * Extract assets from the APK to internal storage.
     */
    private fun extractAssets(assetPath: String, targetDir: java.io.File) {
        if (!targetDir.exists()) {
            targetDir.mkdirs()
        }

        val assetManager = assets
        val files = assetManager.list(assetPath) ?: return

        for (file in files) {
            val assetFilePath = "\$assetPath/\$file"
            val targetFile = targetDir.resolve(file)

            try {
                val subFiles = assetManager.list(assetFilePath)
                if (subFiles != null && subFiles.isNotEmpty()) {
                    // It's a directory
                    extractAssets(assetFilePath, targetFile)
                } else {
                    // It's a file
                    assetManager.open(assetFilePath).use { input ->
                        targetFile.outputStream().use { output ->
                            input.copyTo(output)
                        }
                    }
                }
            } catch (e: Exception) {
                Log.w(TAG, "Failed to extract: \$assetFilePath", e)
            }
        }
    }
}
KOTLIN;

        $filePath = $projectPath . '/app/src/main/java/' . $packagePath . '/LaraNativeApplication.kt';
        file_put_contents($filePath, $content);
    }

    /**
     * Generate the PHP server wrapper class.
     */
    protected function generatePhpServer(string $projectPath, BuildContextInterface $context): void
    {
        $packagePath = str_replace('.', '/', $context->appId());
        $packageName = $context->appId();

        $content = <<<KOTLIN
package {$packageName}

import android.content.Context
import android.util.Log
import java.io.File

/**
 * Manages the embedded PHP runtime process.
 *
 * This class is responsible for starting and stopping the PHP built-in
 * development server that serves the Laravel application locally.
 *
 * NOTE: In a production implementation, this would use a compiled PHP
 * binary for Android (e.g. php-src cross-compiled for ARM). The binary
 * must be included in the assets/php directory during the build process.
 */
class PhpServer(
    private val context: Context,
    private val laravelDir: File,
    private val port: Int
) {
    private val TAG = "PhpServer"
    private var process: Process? = null

    /**
     * Start the PHP built-in server.
     */
    fun start() {
        val phpBinary = getPhpBinaryPath()

        if (phpBinary == null || !File(phpBinary).exists()) {
            Log.e(TAG, "PHP binary not found. The PHP runtime must be bundled during build.")
            Log.e(TAG, "Expected at: \${context.filesDir}/php/php")
            return
        }

        val publicDir = File(laravelDir, "public")
        if (!publicDir.exists()) {
            Log.e(TAG, "Laravel public directory not found at: \${publicDir.absolutePath}")
            return
        }

        try {
            val cmd = arrayOf(
                phpBinary,
                "-S", "127.0.0.1:\$port",
                "-t", publicDir.absolutePath,
                File(publicDir, "index.php").absolutePath
            )

            val env = arrayOf(
                "APP_ENV=production",
                "DB_CONNECTION=sqlite",
                "DB_DATABASE=\${laravelDir.absolutePath}/database/{$context->config('database.filename', 'database.sqlite')}"
            )

            process = Runtime.getRuntime().exec(cmd, env, laravelDir)

            Log.i(TAG, "PHP server started: 127.0.0.1:\$port")

            // Log server output in background
            Thread {
                process?.errorStream?.bufferedReader()?.forEachLine { line ->
                    Log.d(TAG, line)
                }
            }.start()
        } catch (e: Exception) {
            Log.e(TAG, "Failed to start PHP server", e)
        }
    }

    /**
     * Stop the PHP server.
     */
    fun stop() {
        process?.let {
            it.destroy()
            Log.i(TAG, "PHP server stopped")
        }
        process = null
    }

    /**
     * Get the path to the PHP binary.
     */
    private fun getPhpBinaryPath(): String? {
        val phpDir = File(context.filesDir, "php")

        // Check for extracted binary
        val phpBinary = File(phpDir, "php")
        if (phpBinary.exists()) {
            phpBinary.setExecutable(true)
            return phpBinary.absolutePath
        }

        // Extract from assets if available
        try {
            if (!phpDir.exists()) {
                phpDir.mkdirs()
            }

            val assetManager = context.assets
            val phpFiles = assetManager.list("php") ?: return null

            for (file in phpFiles) {
                assetManager.open("php/\$file").use { input ->
                    File(phpDir, file).outputStream().use { output ->
                        input.copyTo(output)
                    }
                }
                File(phpDir, file).setExecutable(true)
            }

            return if (phpBinary.exists()) phpBinary.absolutePath else null
        } catch (e: Exception) {
            Log.e(TAG, "Failed to extract PHP binary", e)
            return null
        }
    }
}
KOTLIN;

        $filePath = $projectPath . '/app/src/main/java/' . $packagePath . '/PhpServer.kt';
        file_put_contents($filePath, $content);
    }

    /**
     * Generate strings.xml
     */
    protected function generateStringsXml(string $projectPath, BuildContextInterface $context): void
    {
        $appName = htmlspecialchars($context->appName(), ENT_XML1);

        $content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">{$appName}</string>
</resources>
XML;

        file_put_contents($projectPath . '/app/src/main/res/values/strings.xml', $content);
    }

    /**
     * Generate styles.xml
     */
    protected function generateStylesXml(string $projectPath, BuildContextInterface $context): void
    {
        $splashColor = $context->config('native.splash.background_color', '#FFFFFF');

        $content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <style name="Theme.LaraNative" parent="Theme.MaterialComponents.DayNight.NoActionBar">
        <item name="android:windowBackground">{$splashColor}</item>
        <item name="colorPrimary">@color/laranative_primary</item>
        <item name="colorPrimaryDark">@color/laranative_primary_dark</item>
    </style>

    <color name="laranative_primary">#1976D2</color>
    <color name="laranative_primary_dark">#1565C0</color>
</resources>
XML;

        file_put_contents($projectPath . '/app/src/main/res/values/styles.xml', $content);
    }

    /**
     * Generate network security config to allow cleartext traffic to localhost.
     */
    protected function generateNetworkSecurityConfig(string $projectPath): void
    {
        $content = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
    <!-- Allow cleartext traffic ONLY to localhost (the embedded PHP server) -->
    <domain-config cleartextTrafficPermitted="true">
        <domain includeSubdomains="false">127.0.0.1</domain>
        <domain includeSubdomains="false">localhost</domain>
    </domain-config>

    <!-- Block cleartext for everything else -->
    <base-config cleartextTrafficPermitted="false">
        <trust-anchors>
            <certificates src="system" />
        </trust-anchors>
    </base-config>
</network-security-config>
XML;

        file_put_contents($projectPath . '/app/src/main/res/xml/network_security_config.xml', $content);
    }

    /**
     * Copy the prepared Laravel application to Android assets.
     */
    protected function copyLaravelApp(BuildContextInterface $context, string $projectPath): void
    {
        $sourceApp = $context->buildPath() . DIRECTORY_SEPARATOR . 'app';
        $targetAssets = $projectPath . '/app/src/main/assets/laravel';

        if (is_dir($sourceApp)) {
            $this->copyDirectory($sourceApp, $targetAssets);
        }
    }

    /**
     * Recursively copy a directory.
     */
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
