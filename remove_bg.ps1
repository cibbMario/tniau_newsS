Add-Type -AssemblyName System.Drawing
$srcPath = "c:\laragon\www\tniau_newsS\assets\img\logo-tniau-new.jpg"
$dstPath = "c:\laragon\www\tniau_newsS\assets\img\logo-tniau-transparent.png"
$img = [System.Drawing.Bitmap]::FromFile($srcPath)
$bmp = New-Object System.Drawing.Bitmap($img.Width, $img.Height)

for ($y = 0; $y -lt $img.Height; $y++) {
    for ($x = 0; $x -lt $img.Width; $x++) {
        $p = $img.GetPixel($x, $y)
        # Threshold for black background
        if ($p.R -lt 45 -and $p.G -lt 45 -and $p.B -lt 45) {
            $bmp.SetPixel($x, $y, [System.Drawing.Color]::Transparent)
        } else {
            $bmp.SetPixel($x, $y, $p)
        }
    }
}
$img.Dispose()
$bmp.Save($dstPath, [System.Drawing.Imaging.ImageFormat]::Png)
$bmp.Dispose()
Write-Host "Image saved to $dstPath"
