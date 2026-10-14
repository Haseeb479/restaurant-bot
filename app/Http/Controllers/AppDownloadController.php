<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class AppDownloadController extends Controller
{
    /**
     * Resolve the location of the APK file.
     */
    protected function getApkPath(): ?string
    {
        $locations = [
            public_path('downloads/Foodio-App.apk'),
            public_path('downloads/Foodio.apk'),
            public_path('installer/Foodio-App.apk'),
            base_path('mobile/android/app/build/outputs/apk/release/app-release.apk'),
            'C:/Users/Seeb/restaurant-bot/mobile/android/app/build/outputs/apk/release/app-release.apk',
            'R:/android/app/build/outputs/apk/release/app-release.apk',
            'C:/b/app/build/outputs/apk/release/app-release.apk',
        ];

        foreach ($locations as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Dedicated APK download page with Chrome instructions.
     */
    public function page()
    {
        $apkPath = $this->getApkPath();
        $isAvailable = $apkPath !== null;
        $fileSize = $isAvailable ? round(File::size($apkPath) / (1024 * 1024), 1) . ' MB' : null;

        return view('download-app', [
            'isAvailable' => $isAvailable,
            'fileSize'    => $fileSize,
            'version'     => '1.0.0',
        ]);
    }

    /**
     * Direct binary APK download endpoint.
     */
    public function download()
    {
        $apkPath = $this->getApkPath();

        if ($apkPath && File::exists($apkPath)) {
            // Ensure public/downloads directory exists and copy for fast static serving
            $publicDir = public_path('downloads');
            if (!File::isDirectory($publicDir)) {
                File::makeDirectory($publicDir, 0755, true);
            }
            $targetPath = public_path('downloads/Foodio-App.apk');
            if ($apkPath !== $targetPath && !File::exists($targetPath)) {
                File::copy($apkPath, $targetPath);
            }

            return Response::download($apkPath, 'Foodio-App.apk', [
                'Content-Type'              => 'application/vnd.android.package-archive',
                'Content-Transfer-Encoding' => 'Binary',
                'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma'                    => 'public',
            ]);
        }

        return redirect()->route('download.app.page')->with('info', 'The APK is currently being finalized. Please check back in 1 minute!');
    }
}
