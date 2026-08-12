</main>

<footer class="app-footer">
    <div style="max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            &copy; <?= date('Y') ?> <strong>Montaseu Studio</strong>. All rights reserved. Interior Design & Architectural Excellence.
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            System Time: <span id="footer-clock">--:--:--</span>
        </div>
    </div>
</footer>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/Montaseu/assets/js/camera.js"></script>
<script src="/Montaseu/assets/js/location.js"></script>
<script src="/Montaseu/assets/js/app.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        updateLiveClock('footer-clock');
    });
</script>

</body>
</html>
