/**
 * Montaseu Studio - Webcam Capture Engine
 */

let cameraStream = null;
let capturedBase64 = null;

function initCamera(videoId, canvasId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);

    if (!video) return;

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } } })
            .then(function(stream) {
                cameraStream = stream;
                video.srcObject = stream;
                video.play();
                document.getElementById('camera-status').style.display = 'none';
            })
            .catch(function(err) {
                console.error("Camera access error:", err);
                const statusEl = document.getElementById('camera-status');
                if (statusEl) {
                    statusEl.innerHTML = `<span style="color:#F87171">Izin Kamera Ditolak / Tidak Ditemukan.<br>Gunakan Upload Foto Manual.</span>`;
                }
                document.getElementById('fallback-photo-group').style.display = 'block';
            });
    } else {
        document.getElementById('fallback-photo-group').style.display = 'block';
    }
}

function takeSnapshot(videoId, canvasId, photoInputId, previewImgId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const photoInput = document.getElementById(photoInputId);
    const previewImg = document.getElementById(previewImgId);

    if (!video || !canvas) return false;

    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    capturedBase64 = canvas.toDataURL('image/jpeg', 0.85);
    if (photoInput) {
        photoInput.value = capturedBase64;
    }
    if (previewImg) {
        previewImg.src = capturedBase64;
        previewImg.style.display = 'block';
        video.style.display = 'none';
    }

    document.getElementById('btn-snap').style.display = 'none';
    document.getElementById('btn-retake').style.display = 'inline-flex';
    return true;
}

function retakeCamera(videoId, previewImgId, photoInputId) {
    const video = document.getElementById(videoId);
    const previewImg = document.getElementById(previewImgId);
    const photoInput = document.getElementById(photoInputId);

    if (video) video.style.display = 'block';
    if (previewImg) previewImg.style.display = 'none';
    if (photoInput) photoInput.value = '';

    capturedBase64 = null;
    document.getElementById('btn-snap').style.display = 'inline-flex';
    document.getElementById('btn-retake').style.display = 'none';
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}
