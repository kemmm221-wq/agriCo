package com.agrico.mobile

import android.content.Intent
import android.net.Uri
import android.os.Build
import androidx.core.content.FileProvider
import com.facebook.react.bridge.ReactApplicationContext
import com.facebook.react.bridge.ReactContextBaseJavaModule
import com.facebook.react.bridge.ReactMethod
import com.facebook.react.bridge.Promise
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

                    // YCbCr skin chrominance locus check
                    val cb = -0.168736 * r - 0.331264 * g + 0.500000 * b + 128.0
                    val cr =  0.500000 * r - 0.418688 * g - 0.081312 * b + 128.0
                    if (cb in 77.0..127.0 && cr in 133.0..173.0 && r > g && g > b) {
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
}
