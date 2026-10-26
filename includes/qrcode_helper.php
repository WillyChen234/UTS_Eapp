<?php
// includes/qrcode_helper.php
// Generator QR Code E-Ticket (Multi-Engine: Online QR Server & Standalone JS/SVG Fallback)

function render_ticket_qrcode($token, $bookingCode, $size = 200) {
    $verifyUrl = BASE_URL . "/verify.php?token=" . urlencode($token);
    $onlineUrl = "https://api.qrserver.com/v1/create-qr-code/?size=" . $size . "x" . $size . "&data=" . urlencode($verifyUrl) . "&margin=10";
    
    // Return structured HTML container that uses the online QR image, with an automatic Canvas fallback powered by qrcode.js
    ob_start();
    ?>
    <div class="ticket-qr-container" style="text-align:center; display:inline-block; padding:12px; background:#ffffff; border-radius:12px; border:2px dashed #057a55; box-shadow:0 4px 12px rgba(0,0,0,0.06);">
        <img src="<?= htmlspecialchars($onlineUrl) ?>" 
             alt="QR Code Tiket <?= htmlspecialchars($bookingCode) ?>"
             id="qr-img-<?= htmlspecialchars($bookingCode) ?>"
             class="img-fluid qr-image" 
             style="max-width:<?= (int)$size ?>px; height:auto; display:block; margin:0 auto;"
             onerror="this.style.display='none'; renderQrFallback('<?= htmlspecialchars($bookingCode) ?>', '<?= addslashes($verifyUrl) ?>', <?= (int)$size ?>);" />
             
        <div id="qr-target-<?= htmlspecialchars($bookingCode) ?>" style="display:none; margin:0 auto; width:<?= (int)$size ?>px; height:<?= (int)$size ?>px;"></div>
        
        <div style="font-family:monospace; font-size:0.75rem; color:#475569; margin-top:8px; font-weight:600; letter-spacing:1px;">
            <?= htmlspecialchars($bookingCode) ?>
        </div>
        <div style="font-size:0.68rem; color:#057a55; font-weight:500;">
            ✓ Terverifikasi Sistem BMN USU
        </div>
    </div>
    <script>
    function renderQrFallback(code, url, sz) {
        const el = document.getElementById('qr-target-' + code);
        if (el) {
            el.style.display = 'block';
            if (typeof QRCode !== 'undefined') {
                new QRCode(el, {
                    text: url,
                    width: sz,
                    height: sz,
                    colorDark : "#057a55",
                    colorLight : "#ffffff"
                });
            }
        }
    }
    </script>
    <?php
    return ob_get_clean();
}
