/**
 * Montaseu Studio - Live Webcam & Camera Capture Engine (No Gallery Upload Allowed)
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
                const statusEl = document.getElementById('camera-status');
                if (statusEl) statusEl.style.display = 'none';
            })
            .catch(function(err) {
                console.error("Camera access error:", err);
                const statusEl = document.getElementById('camera-status');
                if (statusEl) {
                    statusEl.innerHTML = `<span style="color:#F87171; font-weight:bold;"><i class="fas fa-exclamation-triangle"></i> Akses Kamera Ditolak / Tidak Ditemukan.<br>Presensi WAJIB menggunakan foto kamera langsung. Harap izinkan akses kamera browser.</span>`;
                }
            });
    } else {
        const statusEl = document.getElementById('camera-status');
        if (statusEl) {
            statusEl.innerHTML = `<span style="color:#F87171; font-weight:bold;"><i class="fas fa-exclamation-triangle"></i> Browser Anda tidak mendukung pengambilan foto kamera langsung.</span>`;
        }
    }
}

function takeSnapshot(videoId, canvasId, photoInputId, previewImgId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const photoInput = document.getElementById(photoInputId);
    const previewImg = document.getElementById(previewImgId);

    if (!video || !canvas) return false;

    if (!video.videoWidth || video.videoWidth === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Kamera Belum Siap',
                text: 'Harap tunggu hingga feed kamera muncul di layar.',
                background: '#181C23', color: '#F9FAFB'
            });
        } else {
            alert("Kamera belum siap.");
        }
        return false;
    }

    const context = canvas.getContext('2d');
    canvas.width = 480;
    canvas.height = 360;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    capturedBase64 = canvas.toDataURL('image/jpeg', 0.65);
    if (photoInput) {
        photoInput.value = capturedBase64;
    }
    if (previewImg) {
        previewImg.src = capturedBase64;
        previewImg.style.display = 'block';
        video.style.display = 'none';
    }

    const btnSnap = document.getElementById('btn-snap');
    const btnRetake = document.getElementById('btn-retake');
    if (btnSnap) btnSnap.style.display = 'none';
    if (btnRetake) btnRetake.style.display = 'inline-flex';
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
    const btnSnap = document.getElementById('btn-snap');
    const btnRetake = document.getElementById('btn-retake');
    if (btnSnap) btnSnap.style.display = 'inline-flex';
    if (btnRetake) btnRetake.style.display = 'none';
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}
