<!-- Document Viewer Modal -->
<div class="modal fade" id="docViewerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h6 class="modal-title d-flex align-items-center gap-2 text-slate-800 mb-0" style="font-size: 14px; font-weight: 600;">
          <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width:28px;height:28px;flex-shrink:0;">
            <i class="bi bi-file-earmark-text" style="font-size:13px;"></i>
          </div>
          <span id="docViewerTitle">Document Preview</span>
        </h6>
        <div class="d-flex align-items-center gap-2 ms-auto">
          <a id="docViewerDownload" href="#" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" style="font-size: 12px; border-radius: 8px;">
            <i class="bi bi-download"></i> Download
          </a>
          <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 11px;"></button>
        </div>
      </div>
      <div class="modal-body p-0" style="min-height: 500px; background: #f8fafc;">
        <div id="docViewerContent" style="min-height: 500px;"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle doc preview button clicks
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.doc-preview-btn');
        if (!btn) return;
        e.preventDefault();

        var url  = btn.getAttribute('data-url');
        var name = btn.getAttribute('data-name') || 'Document';
        var ext  = url.split('.').pop().toLowerCase().split('?')[0];

        document.getElementById('docViewerTitle').textContent = name;
        document.getElementById('docViewerDownload').setAttribute('href', url);

        var content = '';
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if (imageExts.indexOf(ext) !== -1) {
            content = '<div style="display:flex;align-items:center;justify-content:center;min-height:500px;background:#f1f5f9;padding:16px;">' +
                      '<img src="' + url + '" style="max-height:78vh;max-width:100%;border-radius:8px;object-fit:contain;box-shadow:0 4px 24px rgba(0,0,0,0.12);">' +
                      '</div>';
        } else if (ext === 'pdf') {
            content = '<iframe src="' + url + '" style="width:100%;height:80vh;border:none;display:block;"></iframe>';
        } else {
            content = '<div style="min-height:500px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;">' +
                      '<div style="width:72px;height:72px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">' +
                      '<i class="bi bi-file-earmark-text" style="font-size:32px;color:#0284c7;"></i></div>' +
                      '<h6 style="color:#334155;margin-bottom:8px;">' + name + '</h6>' +
                      '<p style="color:#94a3b8;font-size:13px;margin-bottom:24px;">Preview is not available for this file type.</p>' +
                      '<a href="' + url + '" target="_blank" class="btn btn-primary btn-sm px-4" style="border-radius:8px;">' +
                      '<i class="bi bi-download me-1"></i> Download File</a>' +
                      '</div>';
        }

        document.getElementById('docViewerContent').innerHTML = content;

        // Show modal using Bootstrap JS (already loaded via footer)
        var modal = new bootstrap.Modal(document.getElementById('docViewerModal'));
        modal.show();
    });

    // Clear content on modal close
    document.getElementById('docViewerModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('docViewerContent').innerHTML = '';
    });
});
</script>
