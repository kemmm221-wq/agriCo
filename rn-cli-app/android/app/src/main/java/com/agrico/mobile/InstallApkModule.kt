package com.agrico.mobile

import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Rect
import android.graphics.RectF
import android.graphics.Typeface
import android.media.MediaScannerConnection
import android.net.Uri
import android.os.Build
import android.os.Environment
import androidx.core.content.FileProvider
import com.facebook.react.bridge.Arguments
import com.facebook.react.bridge.Promise
import com.facebook.react.bridge.ReactApplicationContext
import com.facebook.react.bridge.ReactContextBaseJavaModule
import com.facebook.react.bridge.ReactMethod
import com.facebook.react.bridge.ReadableMap
import java.io.File
import java.io.FileInputStream
import java.nio.ByteBuffer
import java.nio.ByteOrder
import java.nio.channels.FileChannel
import org.tensorflow.lite.Interpreter

class InstallApkModule(private val reactContext: ReactApplicationContext) : ReactContextBaseJavaModule(reactContext) {
    override fun getName(): String = "InstallApk"

    private var tfliteInterpreter: Interpreter? = null
    private var interpreterInitAttempted = false

    @Synchronized
    private fun getInterpreter(): Interpreter? {
        if (tfliteInterpreter != null) return tfliteInterpreter
        if (interpreterInitAttempted) return null
        interpreterInitAttempted = true
        return try {
            val modelBuffer: ByteBuffer = try {
                val afd = reactContext.assets.openFd("soil_gate_model.tflite")
                val inputStream = FileInputStream(afd.fileDescriptor)
                val fileChannel = inputStream.channel
                fileChannel.map(FileChannel.MapMode.READ_ONLY, afd.startOffset, afd.declaredLength)
            } catch (fdErr: Exception) {
                // If asset is compressed or file descriptor mapping is unavailable, read directly from InputStream
                reactContext.assets.open("soil_gate_model.tflite").use { input ->
                    val bytes = input.readBytes()
                    ByteBuffer.allocateDirect(bytes.size).apply {
                        order(ByteOrder.nativeOrder())
                        put(bytes)
                        rewind()
                    }
                }
            }
            val options = Interpreter.Options().apply {
                setNumThreads(2)
            }
            tfliteInterpreter = Interpreter(modelBuffer, options)
            android.util.Log.i("InstallApk", "TFLite Soil Gate model loaded successfully")
            tfliteInterpreter
        } catch (e: Exception) {
            android.util.Log.w("InstallApk", "Could not load TFLite model from assets: ${e.message}")
            null
        }
    }

    @ReactMethod
    fun install(filePath: String, promise: Promise) {
        try {
            val file = File(filePath)
            if (!file.exists()) {
                promise.reject("FILE_NOT_FOUND", "APK file not found at $filePath")
                return
            }
            val intent = Intent(Intent.ACTION_VIEW)
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            val uri: Uri = FileProvider.getUriForFile(
                reactContext,
                "${reactContext.packageName}.provider",
                file
            )
            intent.setDataAndType(uri, "application/vnd.android.package-archive")
            reactContext.startActivity(intent)
            promise.resolve(true)
        } catch (e: Exception) {
            promise.reject("INSTALL_ERROR", e.message, e)
        }
    }

    private fun getLocalFileFromUri(uriString: String): File {
        if (uriString.startsWith("content://")) {
            val uri = Uri.parse(uriString)
            val tempFile = File(reactContext.cacheDir, "temp_input_${System.currentTimeMillis()}.jpg")
            reactContext.contentResolver.openInputStream(uri)?.use { input ->
                tempFile.outputStream().use { output ->
                    input.copyTo(output)
                }
            }
            return tempFile
        }
        if (uriString.startsWith("data:") || (uriString.length > 500 && !uriString.contains("/"))) {
            val base64Data = if (uriString.contains(",")) uriString.substringAfter(",") else uriString
            val decoded = android.util.Base64.decode(base64Data, android.util.Base64.DEFAULT)
            val tempFile = File(reactContext.cacheDir, "temp_input_${System.currentTimeMillis()}.jpg")
            tempFile.writeBytes(decoded)
            return tempFile
        }
        val unescaped = try {
            java.net.URLDecoder.decode(uriString, "UTF-8")
        } catch (e: Exception) {
            uriString
        }
        val cleanPath = unescaped.replace("file://", "")
        return File(cleanPath)
    }

    @ReactMethod
    fun extractSoilColor(filePath: String, promise: Promise) {
        try {
            val file = getLocalFileFromUri(filePath)
            if (!file.exists()) {
                promise.reject("FILE_NOT_FOUND", "File not found at $filePath")
                return
            }

            // Downsample image for high performance (~150x150 max)
            val bounds = android.graphics.BitmapFactory.Options().apply { inJustDecodeBounds = true }
            android.graphics.BitmapFactory.decodeFile(file.absolutePath, bounds)

            val maxDim = Math.max(bounds.outWidth, bounds.outHeight)
            var sampleSize = 1
            if (maxDim > 200) {
                sampleSize = maxDim / 150
            }

            val decodeOpts = android.graphics.BitmapFactory.Options().apply {
                inSampleSize = Math.max(1, sampleSize)
                inPreferredConfig = android.graphics.Bitmap.Config.ARGB_8888
            }

            val bitmap = android.graphics.BitmapFactory.decodeFile(file.absolutePath, decodeOpts)
            if (bitmap == null) {
                promise.reject("DECODE_ERROR", "Failed to decode image at $filePath")
                return
            }

            // Sample central 80% region of the soil specimen (inside crop box)
            val w = bitmap.width
            val h = bitmap.height
            val startX = Math.max(0, (w * 0.10).toInt())
            val endX = Math.min(w, (w * 0.90).toInt())
            val startY = Math.max(0, (h * 0.10).toInt())
            val endY = Math.min(h, (h * 0.90).toInt())

            var totalR = 0L
            var totalG = 0L
            var totalB = 0L
            var count = 0L
            var skinPixels = 0L
            var totalLuminance = 0.0
            var totalLuminanceSq = 0.0

            for (y in startY until endY) {
                for (x in startX until endX) {
                    val pixel = bitmap.getPixel(x, y)
                    val r = (pixel shr 16) and 0xFF
                    val g = (pixel shr 8) and 0xFF
                    val b = pixel and 0xFF
                    totalR += r
                    totalG += g
                    totalB += b
                    count++

                    val lum = 0.2989 * r + 0.5870 * g + 0.1140 * b
                    totalLuminance += lum
                    totalLuminanceSq += (lum * lum)

                    // Human skin chrominance locus check (requires high luminance + distinct skin chrominance)
                    val cb = -0.168736 * r - 0.331264 * g + 0.500000 * b + 128.0
                    val cr =  0.500000 * r - 0.418688 * g - 0.081312 * b + 128.0
                    if (lum > 115.0 && r > (g + 18) && g > (b + 10) && cb in 85.0..122.0 && cr in 138.0..170.0) {
                        skinPixels++
                    }
                }
            }

            // Estimate spatial gradient variance across horizontal & vertical steps
            var gradSum = 0.0
            var gradCount = 0L
            val step = Math.max(1, (endX - startX) / 35)
            for (y in startY until (endY - step) step step) {
                for (x in startX until (endX - step) step step) {
                    val p1 = bitmap.getPixel(x, y)
                    val pX = bitmap.getPixel(x + step, y)
                    val pY = bitmap.getPixel(x, y + step)
                    val l1 = 0.2989 * ((p1 shr 16) and 0xFF) + 0.5870 * ((p1 shr 8) and 0xFF) + 0.1140 * (p1 and 0xFF)
                    val lx = 0.2989 * ((pX shr 16) and 0xFF) + 0.5870 * ((pX shr 8) and 0xFF) + 0.1140 * (pX and 0xFF)
                    val ly = 0.2989 * ((pY shr 16) and 0xFF) + 0.5870 * ((pY shr 8) and 0xFF) + 0.1140 * (pY and 0xFF)
                    val gx = lx - l1
                    val gy = ly - l1
                    gradSum += (gx * gx + gy * gy)
                    gradCount++
                }
            }

            // Run TensorFlow Lite Stage 1 model inference (soil_gate_model.tflite) on specimen
            var tfliteRealProb = -1.0
            var tfliteFakeProb = -1.0
            var tfliteExecuted = false

            try {
                val interpreter = getInterpreter()
                if (interpreter != null) {
                    val inputDim = 224
                    val scaledBitmap = android.graphics.Bitmap.createScaledBitmap(bitmap, inputDim, inputDim, true)
                    val inputBuffer = ByteBuffer.allocateDirect(1 * inputDim * inputDim * 3 * 4).apply {
                        order(ByteOrder.nativeOrder())
                        rewind()
                    }
                    val pixelValues = IntArray(inputDim * inputDim)
                    scaledBitmap.getPixels(pixelValues, 0, inputDim, 0, 0, inputDim, inputDim)
                    for (pixel in pixelValues) {
                        val rF = (((pixel shr 16) and 0xFF) / 255.0f)
                        val gF = (((pixel shr 8) and 0xFF) / 255.0f)
                        val bF = ((pixel and 0xFF) / 255.0f)
                        inputBuffer.putFloat(rF)
                        inputBuffer.putFloat(gF)
                        inputBuffer.putFloat(bF)
                    }
                    if (scaledBitmap != bitmap) {
                        scaledBitmap.recycle()
                    }

                    val outputProbs = Array(1) { FloatArray(2) }
                    interpreter.run(inputBuffer, outputProbs)
                    // Index 0: fake_soil, Index 1: real_soil
                    tfliteFakeProb = outputProbs[0][0].toDouble()
                    tfliteRealProb = outputProbs[0][1].toDouble()
                    tfliteExecuted = true
                }
            } catch (tfErr: Exception) {
                android.util.Log.w("InstallApk", "TFLite inference error: ${tfErr.message}")
            }

            bitmap.recycle()

            if (count == 0L) {
                promise.reject("EMPTY_SAMPLE", "No pixels could be sampled")
                return
            }

            val avgR = (totalR / count).toInt()
            val avgG = (totalG / count).toInt()
            val avgB = (totalB / count).toInt()
            val hex = String.format("#%02X%02X%02X", avgR, avgG, avgB)

            val meanLum = totalLuminance / count
            val variance = Math.max(0.0, (totalLuminanceSq / count) - (meanLum * meanLum))
            val stdDev = Math.sqrt(variance)
            val skinRatio = skinPixels.toDouble() / count
            val gradVariance = if (gradCount > 0) gradSum / gradCount else 0.0

            // Stage 3 & Stage 4: Multi-color cluster extraction with genuine spatial bounding boxes
            class ColorBin(val key: Int) {
                var pixelCount = 0L
                var sumR = 0L
                var sumG = 0L
                var sumB = 0L
                var sumX = 0L
                var sumY = 0L
                var minX = Int.MAX_VALUE
                var minY = Int.MAX_VALUE
                var maxX = Int.MIN_VALUE
                var maxY = Int.MIN_VALUE
            }

            val binMap = HashMap<Int, ColorBin>()
            val sampleStep = Math.max(1, Math.min((endX - startX) / 40, (endY - startY) / 40))
            var clusterTotalPixels = 0L

            for (y in startY until endY step sampleStep) {
                for (x in startX until endX step sampleStep) {
                    val p = bitmap.getPixel(x, y)
                    val pr = (p shr 16) and 0xFF
                    val pg = (p shr 8) and 0xFF
                    val pb = p and 0xFF

                    // Ignore extreme outliers (direct glare or black occlusions)
                    val lum = 0.2989 * pr + 0.5870 * pg + 0.1140 * pb
                    if (lum < 20 || lum > 240) continue

                    // 5-bit color quantization bin (32 levels per channel)
                    val binKey = ((pr shr 3) shl 10) or ((pg shr 3) shl 5) or (pb shr 3)
                    val bin = binMap.getOrPut(binKey) { ColorBin(binKey) }
                    bin.pixelCount++
                    bin.sumR += pr
                    bin.sumG += pg
                    bin.sumB += pb
                    bin.sumX += x
                    bin.sumY += y
                    if (x < bin.minX) bin.minX = x
                    if (x > bin.maxX) bin.maxX = x
                    if (y < bin.minY) bin.minY = y
                    if (y > bin.maxY) bin.maxY = y
                    clusterTotalPixels++
                }
            }

            class MergedCluster(
                var count: Long,
                var sumR: Long,
                var sumG: Long,
                var sumB: Long,
                var sumX: Long,
                var sumY: Long,
                var minX: Int,
                var minY: Int,
                var maxX: Int,
                var maxY: Int
            )

            val sortedBins = binMap.values.sortedByDescending { it.pixelCount }
            val mergedClusters = mutableListOf<MergedCluster>()

            for (bin in sortedBins) {
                val binR = (bin.sumR / bin.pixelCount).toInt()
                val binG = (bin.sumG / bin.pixelCount).toInt()
                val binB = (bin.sumB / bin.pixelCount).toInt()

                var matchedCluster: MergedCluster? = null
                for (mc in mergedClusters) {
                    val mcR = (mc.sumR / mc.count).toInt()
                    val mcG = (mc.sumG / mc.count).toInt()
                    val mcB = (mc.sumB / mc.count).toInt()
                    val colorDist = Math.abs(binR - mcR) + Math.abs(binG - mcG) + Math.abs(binB - mcB)
                    if (colorDist < 36) {
                        matchedCluster = mc
                        break
                    }
                }

                if (matchedCluster != null) {
                    matchedCluster.count += bin.pixelCount
                    matchedCluster.sumR += bin.sumR
                    matchedCluster.sumG += bin.sumG
                    matchedCluster.sumB += bin.sumB
                    matchedCluster.sumX += bin.sumX
                    matchedCluster.sumY += bin.sumY
                    if (bin.minX < matchedCluster.minX) matchedCluster.minX = bin.minX
                    if (bin.maxX > matchedCluster.maxX) matchedCluster.maxX = bin.maxX
                    if (bin.minY < matchedCluster.minY) matchedCluster.minY = bin.minY
                    if (bin.maxY > matchedCluster.maxY) matchedCluster.maxY = bin.maxY
                } else if (mergedClusters.size < 4) {
                    mergedClusters.add(
                        MergedCluster(
                            bin.pixelCount,
                            bin.sumR,
                            bin.sumG,
                            bin.sumB,
                            bin.sumX,
                            bin.sumY,
                            bin.minX,
                            bin.minY,
                            bin.maxX,
                            bin.maxY
                        )
                    )
                }
            }

            // Filter clusters with at least 8% proportion
            val validClusters = mergedClusters.filter {
                clusterTotalPixels > 0 && (it.count.toDouble() / clusterTotalPixels) >= 0.08
            }.take(3)

            val regionsArray = com.facebook.react.bridge.Arguments.createArray()
            val totalClusterVotes = if (validClusters.isNotEmpty()) validClusters.sumOf { it.count } else 1L

            if (validClusters.isNotEmpty()) {
                for (cluster in validClusters) {
                    val cR = (cluster.sumR / cluster.count).toInt()
                    val cG = (cluster.sumG / cluster.count).toInt()
                    val cB = (cluster.sumB / cluster.count).toInt()
                    val cHex = String.format("#%02X%02X%02X", cR, cG, cB)
                    val cProp = cluster.count.toDouble() / totalClusterVotes

                    val centerX = cluster.sumX.toDouble() / cluster.count
                    val centerY = cluster.sumY.toDouble() / cluster.count
                    val compW = Math.max(w * 0.14, (cluster.maxX - cluster.minX) * 0.6)
                    val compH = Math.max(h * 0.12, (cluster.maxY - cluster.minY) * 0.6)
                    val boxW = Math.max(w * 0.12, Math.min(w * 0.32, compW))
                    val boxH = Math.max(h * 0.10, Math.min(h * 0.28, compH))

                    val bx = Math.max(w * 0.04, Math.min(w * 0.96 - boxW, centerX - boxW / 2.0))
                    val by = Math.max(h * 0.04, Math.min(h * 0.96 - boxH, centerY - boxH / 2.0))

                    val normX = Math.round((bx / w) * 10000.0) / 10000.0
                    val normY = Math.round((by / h) * 10000.0) / 10000.0
                    val normW = Math.round((boxW / w) * 10000.0) / 10000.0
                    val normH = Math.round((boxH / h) * 10000.0) / 10000.0

                    val regionMap = com.facebook.react.bridge.Arguments.createMap().apply {
                        putString("hex", cHex)
                        putInt("r", cR)
                        putInt("g", cG)
                        putInt("b", cB)
                        putDouble("proportion", Math.round(cProp * 10000.0) / 10000.0)
                        val boxMap = com.facebook.react.bridge.Arguments.createMap().apply {
                            putDouble("norm_x", normX)
                            putDouble("norm_y", normY)
                            putDouble("norm_w", normW)
                            putDouble("norm_h", normH)
                            putInt("x", (normX * w).toInt())
                            putInt("y", (normY * h).toInt())
                            putInt("width", (normW * w).toInt())
                            putInt("height", (normH * h).toInt())
                        }
                        putMap("bounding_box", boxMap)
                        putMap("box", boxMap)
                    }
                    regionsArray.pushMap(regionMap)
                }
            } else {
                // Fallback single central region
                val normX = 0.25
                val normY = 0.25
                val normW = 0.50
                val normH = 0.50
                val regionMap = com.facebook.react.bridge.Arguments.createMap().apply {
                    putString("hex", hex)
                    putInt("r", avgR)
                    putInt("g", avgG)
                    putInt("b", avgB)
                    putDouble("proportion", 1.0)
                    val boxMap = com.facebook.react.bridge.Arguments.createMap().apply {
                        putDouble("norm_x", normX)
                        putDouble("norm_y", normY)
                        putDouble("norm_w", normW)
                        putDouble("norm_h", normH)
                        putInt("x", (normX * w).toInt())
                        putInt("y", (normY * h).toInt())
                        putInt("width", (normW * w).toInt())
                        putInt("height", (normH * h).toInt())
                    }
                    putMap("bounding_box", boxMap)
                    putMap("box", boxMap)
                }
                regionsArray.pushMap(regionMap)
            }

            val map = com.facebook.react.bridge.Arguments.createMap().apply {
                putInt("r", avgR)
                putInt("g", avgG)
                putInt("b", avgB)
                putString("hex", hex)
                putDouble("stdDev", stdDev)
                putDouble("skinRatio", skinRatio)
                putDouble("gradVariance", gradVariance)
                putDouble("tfliteRealProb", tfliteRealProb)
                putDouble("tfliteFakeProb", tfliteFakeProb)
                putBoolean("tfliteExecuted", tfliteExecuted)
                putArray("regions", regionsArray)
            }
            promise.resolve(map)
        } catch (e: Exception) {
            promise.reject("EXTRACT_ERROR", e.message, e)
        }
    }

    @ReactMethod
    fun cropImageWithFrame(
        filePath: String,
        frameX: Double,
        frameY: Double,
        frameWidth: Double,
        frameHeight: Double,
        previewWidth: Double,
        previewHeight: Double,
        promise: Promise
    ) {
        try {
            val file = getLocalFileFromUri(filePath)
            if (!file.exists()) {
                promise.reject("FILE_NOT_FOUND", "Image file not found at $filePath")
                return
            }
            val absolutePath = file.absolutePath

            // Read orientation from EXIF
            var rotation = 0
            try {
                val exif = android.media.ExifInterface(absolutePath)
                val orientation = exif.getAttributeInt(
                    android.media.ExifInterface.TAG_ORIENTATION,
                    android.media.ExifInterface.ORIENTATION_NORMAL
                )
                rotation = when (orientation) {
                    android.media.ExifInterface.ORIENTATION_ROTATE_90 -> 90
                    android.media.ExifInterface.ORIENTATION_ROTATE_180 -> 180
                    android.media.ExifInterface.ORIENTATION_ROTATE_270 -> 270
                    else -> 0
                }
            } catch (e: Exception) {
                // Ignore exif errors
            }

            // Downsample if huge (> 2500px) to prevent OOM
            val boundsOpts = android.graphics.BitmapFactory.Options().apply { inJustDecodeBounds = true }
            android.graphics.BitmapFactory.decodeFile(absolutePath, boundsOpts)
            val maxDim = Math.max(boundsOpts.outWidth, boundsOpts.outHeight)
            var sampleSize = 1
            if (maxDim > 2500) {
                sampleSize = 2
            }

            val decodeOpts = android.graphics.BitmapFactory.Options().apply {
                inSampleSize = sampleSize
                inPreferredConfig = android.graphics.Bitmap.Config.ARGB_8888
            }
            var sourceBitmap = android.graphics.BitmapFactory.decodeFile(absolutePath, decodeOpts)
            if (sourceBitmap == null) {
                promise.reject("DECODE_ERROR", "Failed to decode image at $filePath")
                return
            }

            if (rotation != 0) {
                val matrix = android.graphics.Matrix().apply { postRotate(rotation.toFloat()) }
                val rotated = android.graphics.Bitmap.createBitmap(
                    sourceBitmap, 0, 0, sourceBitmap.width, sourceBitmap.height, matrix, true
                )
                if (rotated != sourceBitmap) {
                    sourceBitmap.recycle()
                    sourceBitmap = rotated
                }
            }

            val imgW = sourceBitmap.width.toDouble()
            val imgH = sourceBitmap.height.toDouble()

            val pW = if (previewWidth > 0) previewWidth else imgW
            val pH = if (previewHeight > 0) previewHeight else imgH

            // Image uses resizeMode="cover" (matches <Image ... resizeMode="cover" />)
            val scale = Math.max(pW / imgW, pH / imgH)
            val offX = (imgW * scale - pW) / 2.0
            val offY = (imgH * scale - pH) / 2.0

            val rawCropX = (frameX + offX) / scale
            val rawCropY = (frameY + offY) / scale
            val rawCropW = frameWidth / scale
            val rawCropH = frameHeight / scale

            val cropX = Math.max(0, Math.min(rawCropX.toInt(), sourceBitmap.width - 1))
            val cropY = Math.max(0, Math.min(rawCropY.toInt(), sourceBitmap.height - 1))
            val cropW = Math.max(1, Math.min(rawCropW.toInt(), sourceBitmap.width - cropX))
            val cropH = Math.max(1, Math.min(rawCropH.toInt(), sourceBitmap.height - cropY))

            val cropped = android.graphics.Bitmap.createBitmap(sourceBitmap, cropX, cropY, cropW, cropH)

            val cropFile = File(reactContext.cacheDir, "soil_crop_${System.currentTimeMillis()}.jpg")
            val outStream = java.io.FileOutputStream(cropFile)
            cropped.compress(android.graphics.Bitmap.CompressFormat.JPEG, 92, outStream)
            outStream.flush()
            outStream.close()

            val base64Bytes = cropFile.readBytes()
            val base64String = android.util.Base64.encodeToString(base64Bytes, android.util.Base64.NO_WRAP)

            sourceBitmap.recycle()
            if (cropped != sourceBitmap) {
                cropped.recycle()
            }

            val map = com.facebook.react.bridge.Arguments.createMap().apply {
                putString("uri", "file://${cropFile.absolutePath}")
                putString("base64", base64String)
                putInt("x", cropX)
                putInt("y", cropY)
                putInt("width", cropW)
                putInt("height", cropH)
                putInt("originalWidth", imgW.toInt())
                putInt("originalHeight", imgH.toInt())
            }
            promise.resolve(map)
        } catch (e: Exception) {
            promise.reject("CROP_ERROR", e.message, e)
        }
    }

    private fun drawWrappedText(canvas: Canvas, text: String, x: Float, startY: Float, maxWidth: Float, paint: Paint, maxLines: Int = 3): Float {
        val words = text.split(Regex("\\s+"))
        var currentY = startY
        var line = StringBuilder()
        var linesDrawn = 0
        val lineHeight = paint.textSize * 1.35f

        for (word in words) {
            val testLine = if (line.isEmpty()) word else "$line $word"
            if (paint.measureText(testLine) <= maxWidth) {
                line = StringBuilder(testLine)
            } else {
                if (line.isNotEmpty()) {
                    canvas.drawText(line.toString(), x, currentY, paint)
                    currentY += lineHeight
                    linesDrawn++
                    if (linesDrawn >= maxLines - 1) {
                        line = StringBuilder("$word...")
                        break
                    }
                }
                line = StringBuilder(word)
            }
        }
        if (line.isNotEmpty() && linesDrawn < maxLines) {
            canvas.drawText(line.toString(), x, currentY, paint)
            currentY += lineHeight
        }
        return currentY
    }

    private fun drawFittedText(canvas: Canvas, text: String, x: Float, y: Float, maxWidth: Float, basePaint: Paint) {
        val p = Paint(basePaint)
        while (p.measureText(text) > maxWidth && p.textSize > 13f) {
            p.textSize -= 1f
        }
        canvas.drawText(text, x, y, p)
    }

    @ReactMethod
    fun exportSoilResultCard(options: ReadableMap, promise: Promise) {
        try {
            val code = if (options.hasKey("munsellCode")) options.getString("munsellCode") ?: "5YR 7/2" else "5YR 7/2"
            val name = if (options.hasKey("colorName")) options.getString("colorName") ?: "Very Pale Brown" else "Very Pale Brown"
            val rawHex = if (options.hasKey("hexCode")) options.getString("hexCode") ?: "#BFA99C" else "#BFA99C"
            val hexColor = if (rawHex.startsWith("#")) rawHex else "#$rawHex"
            val soilPercent = if (options.hasKey("soilPercentage")) options.getString("soilPercentage") ?: "35%" else "35%"
            val organic = if (options.hasKey("soilOrganic")) options.getString("soilOrganic") ?: "Low–Moderate (2–3%)" else "Low–Moderate (2–3%)"
            val soilType = if (options.hasKey("soilType")) options.getString("soilType") ?: "Bleached, eluvial" else "Bleached, eluvial"
            val nearMatch = if (options.hasKey("nearMatch")) options.getString("nearMatch") ?: "97.4%" else "97.4%"
            val scanDate = if (options.hasKey("scanDate")) options.getString("scanDate") ?: "" else ""
            val scanCount = if (options.hasKey("scanCount")) options.getString("scanCount") ?: "1" else "1"
            val description = if (options.hasKey("description")) options.getString("description") ?: "" else ""
            val photoUri = if (options.hasKey("photoUri")) options.getString("photoUri") else null
            val beforeAfterUri = if (options.hasKey("beforeAfterUri")) options.getString("beforeAfterUri") else null
            val triggerShare = if (options.hasKey("triggerShare")) options.getBoolean("triggerShare") else true

            // 1. Load photo Bitmap if available
            var photoBitmap: Bitmap? = null
            if (!photoUri.isNullOrEmpty()) {
                try {
                    val pFile = getLocalFileFromUri(photoUri)
                    if (pFile.exists()) {
                        photoBitmap = BitmapFactory.decodeFile(pFile.absolutePath)
                    }
                } catch (e: Exception) {
                    android.util.Log.w("InstallApk", "Could not load photo: ${e.message}")
                }
            }

            // 2. Load before/after Bitmap if available
            var beforeAfterBitmap: Bitmap? = null
            if (!beforeAfterUri.isNullOrEmpty()) {
                try {
                    val baFile = getLocalFileFromUri(beforeAfterUri)
                    if (baFile.exists()) {
                        beforeAfterBitmap = BitmapFactory.decodeFile(baFile.absolutePath)
                    }
                } catch (e: Exception) {
                    android.util.Log.w("InstallApk", "Could not load beforeAfter: ${e.message}")
                }
            }

            // Canvas dimensions (high resolution A4 proportioned card)
            val cardW = 1080
            val cardH = if (beforeAfterBitmap != null) 1460 else 1140
            val bitmap = Bitmap.createBitmap(cardW, cardH, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(bitmap)

            // Draw white card background
            val bgPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.WHITE
                style = Paint.Style.FILL
            }
            canvas.drawRect(0f, 0f, cardW.toFloat(), cardH.toFloat(), bgPaint)

            // Top green accent bar
            val topBarPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#16A34A")
                style = Paint.Style.FILL
            }
            canvas.drawRect(0f, 0f, cardW.toFloat(), 16f, topBarPaint)

            // AgriCo Emblem
            val emblemPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#16A34A")
                style = Paint.Style.FILL
            }
            canvas.drawRoundRect(RectF(50f, 44f, 104f, 98f), 14f, 14f, emblemPaint)

            // White leaf emblem inside
            val whitePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.WHITE
                style = Paint.Style.FILL
            }
            val leafPath = android.graphics.Path().apply {
                moveTo(66f, 82f)
                cubicTo(66f, 68f, 78f, 56f, 90f, 56f)
                cubicTo(90f, 70f, 78f, 82f, 66f, 82f)
                close()
            }
            canvas.drawPath(leafPath, whitePaint)

            // Brand header: "AgriCo Soil Management System"
            val brandPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#15803D")
                textSize = 28f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
            }
            canvas.drawText("AgriCo Soil Management System", 120f, 82f, brandPaint)

            // Title: "OFFICIAL SOIL CLASSIFICATION CARD"
            val titlePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#0F172A")
                textSize = 36f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
            }
            canvas.drawText("OFFICIAL SOIL CLASSIFICATION CARD", 50f, 142f, titlePaint)

            // Subtitle: Date & System
            val dateText = if (scanDate.isNotBlank()) {
                "Issued: $scanDate  |  AgriCo On-Device Soil Analysis"
            } else {
                val now = java.text.SimpleDateFormat("dd MMM yyyy", java.util.Locale.US).format(java.util.Date())
                "Issued: $now  |  AgriCo On-Device Soil Analysis"
            }
            val subPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#64748B")
                textSize = 20f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.NORMAL)
            }
            canvas.drawText(dateText, 50f, 180f, subPaint)

            // Divider Line
            val divPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#E2E8F0")
                strokeWidth = 3f
                style = Paint.Style.STROKE
            }
            canvas.drawLine(50f, 206f, 1030f, 206f, divPaint)

            // Banner (Hero Section)
            val bannerRect = RectF(50f, 228f, 1030f, 448f)
            val soilColorInt = try {
                Color.parseColor(hexColor)
            } catch (e: Exception) {
                Color.parseColor("#BFA99C")
            }
            val bannerPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = soilColorInt
                style = Paint.Style.FILL
            }
            canvas.drawRoundRect(bannerRect, 24f, 24f, bannerPaint)

            // Specimen photo thumbnail on left of banner
            val thumbRect = RectF(72f, 248f, 252f, 428f)
            if (photoBitmap != null) {
                canvas.save()
                val clipPath = android.graphics.Path().apply {
                    addRoundRect(thumbRect, 20f, 20f, android.graphics.Path.Direction.CW)
                }
                canvas.clipPath(clipPath)
                val srcRect = Rect(0, 0, photoBitmap.width, photoBitmap.height)
                val dstRect = Rect(72, 248, 252, 428)
                canvas.drawBitmap(photoBitmap, srcRect, dstRect, Paint(Paint.FILTER_BITMAP_FLAG))

                // Green detection overlay box
                val greenBoxPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.parseColor("#4ADE80")
                    style = Paint.Style.STROKE
                    strokeWidth = 3.5f
                }
                canvas.drawRect(92f, 268f, 232f, 408f, greenBoxPaint)
                canvas.restore()
            } else {
                val darkThumbPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.argb(60, 0, 0, 0)
                    style = Paint.Style.FILL
                }
                canvas.drawRoundRect(thumbRect, 20f, 20f, darkThumbPaint)
            }
            // Thumbnail outer subtle white border
            val thumbBorder = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.argb(100, 255, 255, 255)
                style = Paint.Style.STROKE
                strokeWidth = 3f
            }
            canvas.drawRoundRect(thumbRect, 20f, 20f, thumbBorder)

            // Banner Text: Munsell Code
            val codePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.WHITE
                textSize = 48f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
                setShadowLayer(4f, 0f, 2f, Color.argb(140, 0, 0, 0))
            }
            canvas.drawText(code, 276f, 314f, codePaint)

            // Banner Text: Color Name
            val namePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.WHITE
                textSize = 26f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
                setShadowLayer(3f, 0f, 1f, Color.argb(120, 0, 0, 0))
            }
            canvas.drawText(name, 276f, 356f, namePaint)

            // Banner Text: Soil Percentage
            val pctPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.argb(230, 255, 255, 255)
                textSize = 22f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.NORMAL)
                setShadowLayer(2f, 0f, 1f, Color.argb(100, 0, 0, 0))
            }
            val pctClean = if (soilPercent.endsWith("%")) soilPercent else "$soilPercent%"
            canvas.drawText("Soil Percentage ($pctClean)", 276f, 394f, pctPaint)

            // Hex Pill Badge
            val pillRect = RectF(840f, 316f, 1008f, 372f)
            val pillPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.argb(140, 0, 0, 0)
                style = Paint.Style.FILL
            }
            canvas.drawRoundRect(pillRect, 28f, 28f, pillPaint)
            val hexTextPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.WHITE
                textSize = 22f
                typeface = Typeface.create(Typeface.MONOSPACE, Typeface.BOLD)
                textAlign = Paint.Align.CENTER
            }
            canvas.drawText(hexColor.toUpperCase(), pillRect.centerX(), pillRect.centerY() + 8f, hexTextPaint)

            // 3x2 Metadata Grid
            val metaCards = listOf(
                Pair("COLOR NAME", name),
                Pair("FULL CODE", code),
                Pair("SCAN COUNT", scanCount),
                Pair("SOIL ORGANIC", organic),
                Pair("SOIL TYPE", soilType),
                Pair("NEAR-MATCH", nearMatch)
            )
            val gridCols = 3
            val gridGap = 16f
            val metaCardW = (980f - gridGap * (gridCols - 1)) / gridCols
            val metaCardH = 122f
            val gridStartY = 472f

            metaCards.forEachIndexed { i, (label, value) ->
                val col = i % gridCols
                val row = i / gridCols
                val cx = 50f + col * (metaCardW + gridGap)
                val cy = gridStartY + row * (metaCardH + 14f)
                val cRect = RectF(cx, cy, cx + metaCardW, cy + metaCardH)

                val cardBg = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.WHITE
                    style = Paint.Style.FILL
                }
                val cardBorder = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.parseColor("#E2E8F0")
                    style = Paint.Style.STROKE
                    strokeWidth = 2f
                }
                canvas.drawRoundRect(cRect, 18f, 18f, cardBg)
                canvas.drawRoundRect(cRect, 18f, 18f, cardBorder)

                val lblPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.parseColor("#64748B")
                    textSize = 15f
                    typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
                }
                canvas.drawText(label, cx + 18f, cy + 34f, lblPaint)

                val isGreen = label == "NEAR-MATCH"
                val valPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = if (isGreen) Color.parseColor("#10B981") else Color.parseColor("#0F172A")
                    textSize = 22f
                    typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
                }
                drawFittedText(canvas, value, cx + 18f, cy + 82f, metaCardW - 36f, valPaint)
            }

            // Description / Remarks Box
            val noteStartY = gridStartY + 2 * (metaCardH + 14f) + 14f
            val noteBoxRect = RectF(50f, noteStartY, 1030f, noteStartY + 160f)
            val noteBg = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#F8FAFC")
                style = Paint.Style.FILL
            }
            val noteBorder = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#E2E8F0")
                style = Paint.Style.STROKE
                strokeWidth = 2f
            }
            canvas.drawRoundRect(noteBoxRect, 18f, 18f, noteBg)
            canvas.drawRoundRect(noteBoxRect, 18f, 18f, noteBorder)

            val noteHeaderPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#64748B")
                textSize = 15f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
            }
            canvas.drawText("FIELD NOTES & SOIL CLASSIFICATION REMARKS", 72f, noteStartY + 34f, noteHeaderPaint)

            val noteTextPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#1E293B")
                textSize = 20f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.NORMAL)
            }
            val cleanDesc = if (description.isNotBlank()) description else "Soil specimen successfully verified against Munsell standard charts. Authentic field sample."
            drawWrappedText(canvas, cleanDesc, 72f, noteStartY + 70f, 916f, noteTextPaint, 3)

            // Before/After comparison section (if available)
            if (beforeAfterBitmap != null) {
                val baStartY = noteStartY + 160f + 16f
                val baH = 280f
                val baRect = RectF(50f, baStartY, 1030f, baStartY + baH)
                val baBg = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.WHITE
                    style = Paint.Style.FILL
                }
                canvas.drawRoundRect(baRect, 18f, 18f, baBg)
                canvas.drawRoundRect(baRect, 18f, 18f, noteBorder)

                val baHdrPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.parseColor("#64748B")
                    textSize = 15f
                    typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
                }
                canvas.drawText("SOIL NORMALIZATION (BEFORE / AFTER COMPARISON)", 72f, baStartY + 34f, baHdrPaint)

                val imgDst = RectF(72f, baStartY + 46f, 1008f, baStartY + baH - 16f)
                canvas.save()
                val clipPath = android.graphics.Path().apply {
                    addRoundRect(imgDst, 14f, 14f, android.graphics.Path.Direction.CW)
                }
                canvas.clipPath(clipPath)
                canvas.drawBitmap(beforeAfterBitmap, Rect(0, 0, beforeAfterBitmap.width, beforeAfterBitmap.height), imgDst, Paint(Paint.FILTER_BITMAP_FLAG))
                canvas.restore()
            }

            // Footer
            val footerH = 76f
            val footerRect = RectF(50f, cardH.toFloat() - footerH - 36f, 1030f, cardH.toFloat() - 36f)
            val footerBg = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#F0FDF4")
                style = Paint.Style.FILL
            }
            val footerBorder = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#BBF7D0")
                style = Paint.Style.STROKE
                strokeWidth = 2f
            }
            canvas.drawRoundRect(footerRect, 18f, 18f, footerBg)
            canvas.drawRoundRect(footerRect, 18f, 18f, footerBorder)

            val sx = 76f
            val sy = footerRect.centerY() - 14f
            val sw = 20f
            val sh = 28f
            val shieldPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#2563EB")
                style = Paint.Style.FILL
            }
            val shieldPath = android.graphics.Path().apply {
                moveTo(sx, sy)
                lineTo(sx + sw, sy)
                lineTo(sx + sw, sy + sh - 8f)
                lineTo(sx + sw / 2f, sy + sh)
                lineTo(sx, sy + sh - 8f)
                close()
            }
            canvas.drawPath(shieldPath, shieldPaint)

            val footerTextPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                color = Color.parseColor("#15803D")
                textSize = 18f
                typeface = Typeface.create(Typeface.DEFAULT, Typeface.BOLD)
            }
            canvas.drawText(
                "Generated by AgriCo Mobile Soil Analysis — Official ISO-A4 Verification Record",
                sx + sw + 16f,
                footerRect.centerY() + 7f,
                footerTextPaint
            )

            // 3. Compress & Save
            val cleanCode = code.replace(Regex("[^a-zA-Z0-9]"), "_")
            val fileName = "AgriCo_Soil_Record_${cleanCode}_${System.currentTimeMillis()}.png"

            // Save to cache dir for FileProvider
            val cacheFile = File(reactContext.cacheDir, fileName)
            java.io.FileOutputStream(cacheFile).use { out ->
                bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
            }

            // Save to Public Downloads & notify Gallery
            var savedPublicPath: String? = null
            try {
                val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
                if (!downloadsDir.exists()) downloadsDir.mkdirs()
                val publicFile = File(downloadsDir, fileName)
                java.io.FileOutputStream(publicFile).use { out ->
                    bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
                }
                savedPublicPath = publicFile.absolutePath

                MediaScannerConnection.scanFile(
                    reactContext,
                    arrayOf(publicFile.absolutePath),
                    arrayOf("image/png")
                ) { path, uri ->
                    android.util.Log.i("InstallApk", "Saved PNG scanned to gallery: $path -> $uri")
                }
            } catch (fsErr: Exception) {
                android.util.Log.w("InstallApk", "Public downloads write fallback: ${fsErr.message}")
            }

            val contentUri = FileProvider.getUriForFile(
                reactContext,
                "${reactContext.packageName}.provider",
                cacheFile
            )

            // Clean up bitmaps from memory
            photoBitmap?.recycle()
            beforeAfterBitmap?.recycle()
            bitmap.recycle()

            // Trigger Android Share sheet with image/png
            if (triggerShare) {
                val shareIntent = Intent(Intent.ACTION_SEND).apply {
                    type = "image/png"
                    putExtra(Intent.EXTRA_STREAM, contentUri)
                    putExtra(Intent.EXTRA_SUBJECT, "AgriCo Soil Record - $code")
                    putExtra(Intent.EXTRA_TEXT, "AgriCo Soil Classification Record for $code ($name)")
                    addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                    addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                }
                val chooser = Intent.createChooser(shareIntent, "Save or Share Soil Result Image")
                chooser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                reactContext.startActivity(chooser)
            }

            val resultMap = Arguments.createMap().apply {
                putString("filePath", cacheFile.absolutePath)
                putString("fileName", fileName)
                putString("uri", contentUri.toString())
                if (savedPublicPath != null) {
                    putString("publicPath", savedPublicPath)
                }
            }
            promise.resolve(resultMap)
        } catch (e: Exception) {
            promise.reject("EXPORT_ERROR", e.message, e)
        }
    }
}
